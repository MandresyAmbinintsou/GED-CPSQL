// scanner_optimized.c - Version avec COPY pour insertion massive
// Compilation: gcc -o scanner scanner_optimized.c -lpq -lpthread -lssl -lcrypto -O3

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#ifdef _WIN32
#include <windows.h>
#include <io.h>
#include <direct.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <dirent.h>
#define PATH_SEPARATOR '\\'
#define strcasecmp _stricmp
#define strncasecmp _strnicmp
#define snprintf _snprintf
#ifndef S_ISDIR
#define S_ISDIR(mode) (((mode) & S_IFMT) == S_IFDIR)
#endif
#define stat _stat
#else
#include <dirent.h>
#include <sys/stat.h>
#include <unistd.h>
#endif
#include <libpq-fe.h>
#include <pthread.h>
#include <openssl/md5.h>
#include <errno.h>

#ifndef MAX_PATH
#define MAX_PATH 4096
#endif
#define MAX_THREADS 8
#define BATCH_SIZE 50000  // Augmenté à 50000 pour COPY
#define MAX_TYPES 1000

// Cache des types de dossiers
typedef struct {
    char code[256];
    int id;
} TypeCache;

TypeCache type_cache[MAX_TYPES];
int type_cache_count = 0;
pthread_mutex_t type_cache_mutex = PTHREAD_MUTEX_INITIALIZER;

typedef struct {
    char matricule[256];
    char type_dossier[256];
    char chemin_png[MAX_PATH];
    char nom_fichier[256];
    off_t taille;
    char hash_md5[33];
    time_t date_modif;
} DocumentInfo;

typedef struct {
    char chemin_base[MAX_PATH];
    PGconn *conn;
    int thread_id;
    long long documents_trouves;
} ThreadData;

void calculer_md5(const char *chemin, char *output) {
    unsigned char c[MD5_DIGEST_LENGTH];
    MD5_CTX mdContext;
    FILE *file = fopen(chemin, "rb");
    if (!file) {
        output[0] = '\0';
        return;
    }
    
    unsigned char buffer[8192];
    size_t bytes;
    MD5_Init(&mdContext);
    while ((bytes = fread(buffer, 1, sizeof(buffer), file)) != 0) {
        MD5_Update(&mdContext, buffer, bytes);
    }
    MD5_Final(c, &mdContext);
    fclose(file);
    
    for (int i = 0; i < MD5_DIGEST_LENGTH; i++) {
        sprintf(output + (i * 2), "%02x", c[i]);
    }
    output[32] = '\0';
}

int est_image(const char *nom) {
    const char *ext = strrchr(nom, '.');
    if (!ext) return 0;
    ext++;
    return (strcasecmp(ext, "png") == 0 || strcasecmp(ext, "jpg") == 0 ||
            strcasecmp(ext, "jpeg") == 0 || strcasecmp(ext, "tif") == 0 ||
            strcasecmp(ext, "tiff") == 0);
}

// Insertion massive avec COPY - BEAUCOUP PLUS RAPIDE
void inserer_lot_copy(PGconn *conn, DocumentInfo *docs, int count) {
    if (count == 0) return;
    
    PGresult *res;
    char *copy_cmd = "COPY documents (matricule, type_dossier_nom, nom_fichier, chemin_png, taille_bytes, hash_md5, date_scan) FROM STDIN WITH (FORMAT csv, DELIMITER '|')";
    
    res = PQexec(conn, copy_cmd);
    if (PQresultStatus(res) != PGRES_COPY_IN) {
        fprintf(stderr, "Erreur COPY: %s\n", PQerrorMessage(conn));
        PQclear(res);
        return;
    }
    PQclear(res);
    
    // Construction des données en mémoire
    size_t total_size = 0;
    for (int i = 0; i < count; i++) {
        total_size += strlen(docs[i].matricule) + strlen(docs[i].type_dossier) + 
                      strlen(docs[i].nom_fichier) + strlen(docs[i].chemin_png) + 100;
    }
    
    char *data = malloc(total_size + 1);
    char *ptr = data;
    
    for (int i = 0; i < count; i++) {
        ptr += sprintf(ptr, "%s|%s|%s|%s|%ld|%s|%ld\n",
            docs[i].matricule,
            docs[i].type_dossier,
            docs[i].nom_fichier,
            docs[i].chemin_png,
            docs[i].taille,
            docs[i].hash_md5,
            docs[i].date_modif);
    }
    
    int ret = PQputCopyData(conn, data, ptr - data);
    if (ret != 1) {
        fprintf(stderr, "Erreur envoi COPY data: %s\n", PQerrorMessage(conn));
    }
    
    ret = PQputCopyEnd(conn, NULL);
    if (ret != 1) {
        fprintf(stderr, "Erreur fin COPY: %s\n", PQerrorMessage(conn));
    }
    
    free(data);
    
    // Récupérer le résultat
    res = PQgetResult(conn);
    if (PQresultStatus(res) != PGRES_COMMAND_OK) {
        fprintf(stderr, "Erreur COPY: %s\n", PQerrorMessage(conn));
    }
    PQclear(res);
}

// Version itérative du scan (évite stack overflow)
void scan_dossier_iteratif(const char *chemin_depart, const char *matricule, const char *type_dossier_initial,
                           PGconn *conn, int *count, DocumentInfo *batch) {
    // Structure pour la pile manuelle
    typedef struct {
        char chemin[MAX_PATH];
        char matricule[256];
        char type_dossier[256];
    } StackEntry;
    
    StackEntry *stack = malloc(sizeof(StackEntry) * 10000);
    int stack_ptr = 0;
    
    strcpy(stack[stack_ptr].chemin, chemin_depart);
    strcpy(stack[stack_ptr].matricule, matricule);
    strcpy(stack[stack_ptr].type_dossier, type_dossier_initial);
    stack_ptr++;
    
    while (stack_ptr > 0) {
        stack_ptr--;
        StackEntry current = stack[stack_ptr];
        
        DIR *dir = opendir(current.chemin);
        if (!dir) continue;
        
        struct dirent *entry;
        while ((entry = readdir(dir)) != NULL) {
            if (strcmp(entry->d_name, ".") == 0 || strcmp(entry->d_name, "..") == 0)
                continue;
            
            char chemin_complet[MAX_PATH];
            snprintf(chemin_complet, sizeof(chemin_complet), "%s/%s", current.chemin, entry->d_name);
            
            struct stat st;
            if (stat(chemin_complet, &st) == 0) {
                if (S_ISDIR(st.st_mode)) {
                    // Ajouter à la pile
                    if (stack_ptr < 10000 - 1) {
                        strcpy(stack[stack_ptr].chemin, chemin_complet);
                        strcpy(stack[stack_ptr].matricule, current.matricule);
                        strcpy(stack[stack_ptr].type_dossier, entry->d_name);
                        stack_ptr++;
                    } else {
                        fprintf(stderr, "Pile pleine, dossier ignoré: %s\n", chemin_complet);
                    }
                }
                else if (S_ISREG(st.st_mode) && est_image(entry->d_name)) {
                    if (*count >= BATCH_SIZE) {
                        inserer_lot_copy(conn, batch, *count);
                        *count = 0;
                    }
                    
                    DocumentInfo *doc = &batch[*count];
                    strncpy(doc->matricule, current.matricule, sizeof(doc->matricule) - 1);
                    strncpy(doc->type_dossier, current.type_dossier, sizeof(doc->type_dossier) - 1);
                    strncpy(doc->chemin_png, chemin_complet, sizeof(doc->chemin_png) - 1);
                    strncpy(doc->nom_fichier, entry->d_name, sizeof(doc->nom_fichier) - 1);
                    doc->taille = st.st_size;
                    doc->date_modif = st.st_mtime;
                    calculer_md5(chemin_complet, doc->hash_md5);
                    
                    (*count)++;
                }
            }
        }
        closedir(dir);
    }
    
    free(stack);
}

void *scan_worker(void *arg) {
    ThreadData *data = (ThreadData*)arg;
    DIR *dir;
    struct dirent *entry;
    char chemin_matricule[MAX_PATH];
    int batch_count = 0;
    DocumentInfo *batch = malloc(sizeof(DocumentInfo) * BATCH_SIZE);
    
    if (!batch) {
        fprintf(stderr, "Thread %d: Erreur allocation mémoire\n", data->thread_id);
        return NULL;
    }
    
    dir = opendir(data->chemin_base);
    if (!dir) {
        fprintf(stderr, "Thread %d: Impossible d'ouvrir %s\n", data->thread_id, data->chemin_base);
        free(batch);
        return NULL;
    }
    
    // Compter le nombre total de dossiers matricule pour répartition
    int total_matricules = 0;
    struct dirent **entries = NULL;
    int n = scandir(data->chemin_base, &entries, NULL, alphasort);
    
    if (n < 0) {
        closedir(dir);
        free(batch);
        return NULL;
    }
    
    // Répartition par thread
    for (int i = 0; i < n; i++) {
        entry = entries[i];
        if (strcmp(entry->d_name, ".") == 0 || strcmp(entry->d_name, "..") == 0) {
            free(entries[i]);
            continue;
        }
        
        // Distribution modulo MAX_THREADS
        if (i % MAX_THREADS != data->thread_id) {
            free(entries[i]);
            continue;
        }
        
        snprintf(chemin_matricule, sizeof(chemin_matricule), "%s/%s", data->chemin_base, entry->d_name);
        
        struct stat st;
        if (stat(chemin_matricule, &st) == 0 && S_ISDIR(st.st_mode)) {
            printf("Thread %d: Scan du matricule %s\n", data->thread_id, entry->d_name);
            scan_dossier_iteratif(chemin_matricule, entry->d_name, "documents", 
                                  data->conn, &batch_count, batch);
            data->documents_trouves += batch_count;
        }
        
        free(entries[i]);
    }
    
    free(entries);
    closedir(dir);
    
    if (batch_count > 0) {
        inserer_lot_copy(data->conn, batch, batch_count);
    }
    
    free(batch);
    return NULL;
}

int main(int argc, char *argv[]) {
    if (argc < 2) {
        fprintf(stderr, "Usage: %s <dossier_archives>\n", argv[0]);
        return 1;
    }
    
    const char *dossier_base = argv[1];
    printf("=== SCAN OPTIMISÉ DES ARCHIVES ===\n");
    printf("Dossier: %s\n", dossier_base);
    printf("Threads: %d\n", MAX_THREADS);
    printf("Batch size: %d\n", BATCH_SIZE);
    
    PGconn *conn = PQconnectdb("host=localhost dbname=archives_db user=postgres password=postgres");
    if (PQstatus(conn) != CONNECTION_OK) {
        fprintf(stderr, "Erreur connexion DB: %s\n", PQerrorMessage(conn));
        PQfinish(conn);
        return 1;
    }
    
    // Tables optimisées avec partitionnement
    const char *create_tables = 
        "CREATE TABLE IF NOT EXISTS employes ("
        "  matricule VARCHAR(50) PRIMARY KEY,"
        "  nom VARCHAR(100),"
        "  prenom VARCHAR(100),"
        "  actif BOOLEAN DEFAULT true,"
        "  created_at TIMESTAMP DEFAULT NOW()"
        ");"
        "CREATE TABLE IF NOT EXISTS types_dossiers ("
        "  id SERIAL PRIMARY KEY,"
        "  code VARCHAR(50) UNIQUE NOT NULL,"
        "  libelle VARCHAR(200) NOT NULL,"
        "  ordre_affichage INTEGER DEFAULT 0"
        ");"
        "CREATE TABLE IF NOT EXISTS documents ("
        "  id SERIAL,"
        "  matricule VARCHAR(50) REFERENCES employes(matricule),"
        "  type_dossier_nom VARCHAR(100) NOT NULL,"
        "  nom_fichier VARCHAR(255) NOT NULL,"
        "  chemin_png TEXT UNIQUE NOT NULL,"
        "  taille_bytes BIGINT,"
        "  hash_md5 VARCHAR(32),"
        "  date_scan DATE,"
        "  created_at TIMESTAMP DEFAULT NOW()"
        ") PARTITION BY HASH (matricule);"
        "CREATE TABLE IF NOT EXISTS scan_history ("
        "  chemin TEXT PRIMARY KEY,"
        "  dernier_scan TIMESTAMP NOT NULL"
        ");";
    
    PGresult *res = PQexec(conn, create_tables);
    if (PQresultStatus(res) != PGRES_COMMAND_OK) {
        fprintf(stderr, "Erreur création tables: %s\n", PQerrorMessage(conn));
    }
    PQclear(res);
    
    // Créer 100 partitions pour meilleure distribution
    for (int i = 0; i < 100; i++) {
        char sql[256];
        snprintf(sql, sizeof(sql), 
            "CREATE TABLE IF NOT EXISTS documents_%d PARTITION OF documents FOR VALUES WITH (MODULUS 100, REMAINDER %d);",
            i, i);
        res = PQexec(conn, sql);
        PQclear(res);
    }
    
    // Index optimisés
    const char *indexes[] = {
        "CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_documents_matricule_type ON documents(matricule, type_dossier_nom);",
        "CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_documents_date_scan ON documents(date_scan);",
        "CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_employes_recherche ON employes USING GIN(to_tsvector('french', nom || ' ' || COALESCE(prenom, '') || ' ' || matricule));"
    };
    
    for (int i = 0; i < 3; i++) {
        res = PQexec(conn, indexes[i]);
        PQclear(res);
    }
    
    // Désactiver les triggers et contraintes pendant l'insertion massive
    PQexec(conn, "SET session_replication_role = replica;");
    
    pthread_t threads[MAX_THREADS];
    ThreadData thread_data[MAX_THREADS];
    
    printf("\nDémarrage des threads...\n");
    time_t start_time = time(NULL);
    
    for (int i = 0; i < MAX_THREADS; i++) {
        thread_data[i].thread_id = i;
        strcpy(thread_data[i].chemin_base, dossier_base);
        thread_data[i].conn = PQconnectdb("host=localhost dbname=archives_db user=postgres password=postgres");
        thread_data[i].documents_trouves = 0;
        pthread_create(&threads[i], NULL, scan_worker, &thread_data[i]);
    }
    
    long long total_documents = 0;
    for (int i = 0; i < MAX_THREADS; i++) {
        pthread_join(threads[i], NULL);
        total_documents += thread_data[i].documents_trouves;
        PQfinish(thread_data[i].conn);
    }
    
    // Réactiver les contraintes
    PQexec(conn, "SET session_replication_role = DEFAULT;");
    
    // ANALYZE pour mettre à jour les statistiques
    PQexec(conn, "ANALYZE;");
    
    time_t end_time = time(NULL);
    int duration = (int)(end_time - start_time);
    
    printf("\n=== SCAN TERMINÉ ===\n");
    printf("Documents trouvés: %lld\n", total_documents);
    printf("Temps écoulé: %d secondes (%.1f minutes)\n", duration, duration / 60.0);
    printf("Vitesse: %.0f documents/seconde\n", (double)total_documents / duration);
    
    PQfinish(conn);
    return 0;
}