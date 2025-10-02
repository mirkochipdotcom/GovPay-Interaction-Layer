#!/bin/bash

# Uscita immediata in caso di errore
set -e

# Variabili di configurazione
CONFIG_FILE="api_config.json"
OUTPUT_DIR="generated-clients"

# Cleanup iniziale (opzionale)
rm -rf "$OUTPUT_DIR"
mkdir -p "$OUTPUT_DIR"

# Verifica dipendenze
if ! command -v jq &> /dev/null
then
    echo "Errore: 'jq' non è installato. Installalo (es. sudo apt install jq)."
    exit 1
fi

# Variabile per il flag -i di sed (necessario per macOS vs Linux)
SED_INPLACE=""
if [[ "$OSTYPE" == "darwin"* ]]; then
    SED_INPLACE="''"
fi

# Leggi la configurazione e itera su ogni API
NUM_APIS=$(jq length "$CONFIG_FILE")

for i in $(seq 0 $((NUM_APIS - 1))); do
    API_NAME=$(jq -r ".[$i].name" "$CONFIG_FILE")
    API_VERSION=$(jq -r ".[$i].version" "$CONFIG_FILE")
    BASE_URL=$(jq -r ".[$i].base_url" "$CONFIG_FILE")
    MAIN_FILE=$(jq -r ".[$i].main_file" "$CONFIG_FILE")
    CLIENT_DIR=$(jq -r ".[$i].client_dir" "$CONFIG_FILE")
    CLIENT_NAMESPACE=$(jq -r ".[$i].client_namespace" "$CONFIG_FILE")
    SUPPORT_FILES=$(jq -r ".[$i].support_files | @sh" "$CONFIG_FILE")
    HIDDEN_DEPS=$(jq -r ".[$i].hidden_dependencies | .[]?" "$CONFIG_FILE")

    WORKING_DIR="$OUTPUT_DIR/$API_NAME-$API_VERSION"
    BUNDLED_FILE="$API_NAME.bundled.yaml"

    echo "====================================================================="
    echo "🏁 INIZIO PROCESSO: $API_NAME ($API_VERSION)"
    echo "====================================================================="
    
    # 1. Creazione e navigazione nella cartella isolata
    mkdir -p "$WORKING_DIR"
    echo "   > Creato ambiente isolato: $WORKING_DIR"
    
    # 2. Download dei file principali e di supporto
    echo "   > Download $MAIN_FILE da $BASE_URL..."
    curl -s -o "$WORKING_DIR/$MAIN_FILE" "$BASE_URL/$MAIN_FILE"

    for file in $SUPPORT_FILES; do
        filename=$(echo "$file" | tr -d "'")
        echo "   > Download file di supporto: $filename"
        curl -s -o "$WORKING_DIR/$filename" "$BASE_URL/$filename"
    done
    
    # 3. Download delle dipendenze nascoste (se presenti)
    if [[ -n "$HIDDEN_DEPS" ]]; then
        echo "   > Download DIPENDENZE NASCOSTE (Utente V1)..."
        for dep_url in $HIDDEN_DEPS; do
            dep_filename=$(basename "$dep_url")
            curl -s -o "$WORKING_DIR/$dep_filename" "$dep_url"
        done
    fi

    # 4. Applicazione delle correzioni SED (se presenti)
    CORRECTIONS=$(jq -r ".[$i].corrections | .[]?" "$CONFIG_FILE")
    if [[ -n "$CORRECTIONS" ]]; then
        echo "   > Applicazione correzioni SED..."

    FORCE_FLAG=""
    if [[ "$API_NAME" == "ragioneria" ]]; then
        FORCE_FLAG="--force"
        echo "   > ATTENZIONE: Bundling Ragioneria con l'opzione --force per ignorare i riferimenti rotti."
    fi
        
        # Naviga nella cartella per eseguire SED in sicurezza
        (
            cd "$WORKING_DIR"
            for correction in $CORRECTIONS; do
                echo "     - Applica: $correction"
                
                # Sostituiamo i pipe | con un carattere che non confonda Bash, poi eseguiamo.
                # Questa è la sintassi più robusta per gestire i percorsi YAML con sed.
                ESCAPED_CORRECTION=$(echo "$correction" | sed 's|#|\\#|g')
                
                # Eseguiamo il comando sed: usiamo il pipe come delimitatore.
                eval "sed -i $SED_INPLACE '$ESCAPED_CORRECTION' $MAIN_FILE"
            done
        )
    fi
    # 5. Esecuzione del Bundling Docker
    echo "   > Esecuzione Bundling ($MAIN_FILE)..."
    docker run --rm \
        --user "$(id -u):$(id -g)" \
        -v "$(pwd)/$WORKING_DIR:/data" \
        redocly/cli:latest bundle \
        "/data/$MAIN_FILE" \
        --output "/data/$BUNDLED_FILE" \
        $FORCE_FLAG # <--- AGGIUNTO QUI!

    # 6. Generazione del Client PHP
    echo "   > Generazione Client PHP ($CLIENT_DIR)..."
    docker run --rm \
        --user "$(id -u):$(id -g)" \
        -v "$(pwd)/$WORKING_DIR:/local" \
        openapitools/openapi-generator-cli generate \
        -i "/local/$BUNDLED_FILE" \
        -g php \
        -o "/local/$CLIENT_DIR" \
        --invoker-package "$CLIENT_NAMESPACE"

    echo "✅ CLIENT $API_NAME GENERATO CON SUCCESSO in $WORKING_DIR/$CLIENT_DIR"
done

echo "====================================================================="
echo "🎉 TUTTI I CLIENT SONO STATI GENERATI"
echo "====================================================================="
