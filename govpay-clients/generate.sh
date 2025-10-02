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
# (Non lo usiamo per la correzione composer.json, ma lo lasciamo per le correzioni YAML)
SED_INPLACE=""
if [[ "$OSTYPE" == "darwin"* ]]; then
    SED_INPLACE="''"
fi

# Leggi la configurazione e itera su ogni API
NUM_APIS=$(jq length "$CONFIG_FILE")

for i in $(seq 0 $((NUM_APIS - 1))); do
    # Estrazione di tutti i campi
    API_NAME=$(jq -r ".[$i].name" "$CONFIG_FILE")
    API_VERSION=$(jq -r ".[$i].version" "$CONFIG_FILE")
    BASE_URL=$(jq -r ".[$i].base_url" "$CONFIG_FILE")
    MAIN_FILE=$(jq -r ".[$i].main_file" "$CONFIG_FILE")
    CLIENT_DIR=$(jq -r ".[$i].client_dir" "$CONFIG_FILE")
    CLIENT_NAMESPACE=$(jq -r ".[$i].client_namespace" "$CONFIG_FILE")
    PACKAGE_NAME=$(jq -r ".[$i].package_name" "$CONFIG_FILE") # <--- NUOVO CAMPO!
    SUPPORT_FILES=$(jq -r ".[$i].support_files | @sh" "$CONFIG_FILE")
    HIDDEN_DEPS=$(jq -r ".[$i].hidden_dependencies | .[]?" "$CONFIG_FILE")

    WORKING_DIR="$OUTPUT_DIR/$API_NAME-$API_VERSION"
    BUNDLED_FILE="$API_NAME.bundled.yaml"

    echo "====================================================================="
    echo "🏁 INIZIO PROCESSO: $API_NAME ($API_VERSION) - Pacchetto: $PACKAGE_NAME"
    echo "====================================================================="
    
    # ... (le fasi 1, 2, 3, 4 e 5 rimangono invariate) ...
    
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
                
                ESCAPED_CORRECTION=$(echo "$correction" | sed 's|#|\\#|g')
                
                # Eseguiamo il comando sed
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
        $FORCE_FLAG

    # 6. Generazione del Client PHP
    echo "   > Generazione Client PHP ($CLIENT_DIR)..."
    docker run --rm \
        --user "$(id -u):$(id -g)" \
        -v "$(pwd)/$WORKING_DIR:/local" \
        openapitools/openapi-generator-cli generate \
        -i "/local/$BUNDLED_FILE" \
        -g php \
        -o "/local/$CLIENT_DIR" \
        --invoker-package "$CLIENT_NAMESPACE" \
        --additional-properties packageName="$CLIENT_NAMESPACE" # Usa il namespace per coerenza

    # 7. CORREZIONE FINALE: Aggiunta del campo 'name' E INIEZIONE PSR-4 con jq
    COMPOSER_FILE="$WORKING_DIR/$CLIENT_DIR/composer.json"
    
    if [ -f "$COMPOSER_FILE" ]; then
        echo "   > 🔨 CORREZIONE FINALE: Iniezione del campo \"name\" e \"autoload\""
        
        # 1. Definisci il namespace (es: GovPay\Pagamenti\) e il percorso (es: lib/)
        NAMESPACE_KEY="${CLIENT_NAMESPACE}\\\\" # Il namespace finisce con doppio slash
        VENDOR_PATH="lib/"                     # La cartella che contiene il codice generato
        
        # 2. Crea l'oggetto JSON per l'autoloading
        AUTOLOAD_JSON=$(cat <<EOF
        {
          "autoload": {
            "psr-4": {
              "$NAMESPACE_KEY": "$VENDOR_PATH"
            }
          }
        }
EOF
        )
        
        # 3. Usa jq per unire l'oggetto autoload e il campo name nel file
        # Il flag -s serve a leggere la stringa JSON creata
        jq --arg name "$PACKAGE_NAME" \
           --slurpfile autoload <(echo "$AUTOLOAD_JSON") \
           '. + {name: $name} + .autoload' \
           "$COMPOSER_FILE" > temp.json && \
        mv temp.json "$COMPOSER_FILE"
    else
        echo "   > ATTENZIONE: File composer.json non trovato in $COMPOSER_FILE"
    fi

    echo "✅ CLIENT $API_NAME GENERATO E CORRETTO CON SUCCESSO in $WORKING_DIR/$CLIENT_DIR"
done

echo "====================================================================="
echo "🎉 TUTTI I CLIENT SONO STATI GENERATI E I FILE composer.json CORRETTI"
echo "====================================================================="
