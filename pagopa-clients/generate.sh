#!/bin/bash

set -e

CONFIG_FILE="api_config.json"
OUTPUT_DIR="generated-clients"

mkdir -p "$OUTPUT_DIR"

if ! command -v jq &> /dev/null
then
    echo "Errore: 'jq' non è installato. Installalo (es. sudo apt install jq)."
    exit 1
fi

NUM_APIS=$(jq length "$CONFIG_FILE")

for i in $(seq 0 $((NUM_APIS - 1))); do
    API_NAME=$(jq -r ".[$i].name" "$CONFIG_FILE")
    API_VERSION=$(jq -r ".[$i].version" "$CONFIG_FILE")
    BASE_URL=$(jq -r ".[$i].base_url" "$CONFIG_FILE")
    MAIN_FILE=$(jq -r ".[$i].main_file" "$CONFIG_FILE")
    CLIENT_DIR=$(jq -r ".[$i].client_dir" "$CONFIG_FILE")
    CLIENT_NAMESPACE=$(jq -r ".[$i].client_namespace" "$CONFIG_FILE")
    PACKAGE_NAME=$(jq -r ".[$i].package_name" "$CONFIG_FILE")

    WORKING_DIR="$OUTPUT_DIR/$API_NAME-$API_VERSION"
    BUNDLED_FILE="$API_NAME.bundled.json"

    echo "====================================================================="
    echo "INIZIO PROCESSO: $API_NAME ($API_VERSION) - Pacchetto: $PACKAGE_NAME"
    echo "====================================================================="

    mkdir -p "$WORKING_DIR"
    echo "   > Download $MAIN_FILE da $BASE_URL..."
    curl -s -o "$WORKING_DIR/$MAIN_FILE" "$BASE_URL/$MAIN_FILE"

    echo "   > Bundling (nessuna dipendenza attesa)..."
    docker run --rm \
        --user "$(id -u):$(id -g)" \
        -v "$(pwd)/$WORKING_DIR:/data" \
        redocly/cli:latest bundle \
        "/data/$MAIN_FILE" \
        --output "/data/$BUNDLED_FILE"

    echo "   > Generazione Client PHP ($CLIENT_DIR)..."
    docker run --rm \
        --user "$(id -u):$(id -g)" \
        -v "$(pwd)/$WORKING_DIR:/local" \
        openapitools/openapi-generator-cli generate \
        -i "/local/$BUNDLED_FILE" \
        -g php \
        -o "/local/$CLIENT_DIR" \
        --invoker-package "$CLIENT_NAMESPACE" \
        --additional-properties packageName="$CLIENT_NAMESPACE"

    COMPOSER_FILE="$WORKING_DIR/$CLIENT_DIR/composer.json"
    if [ -f "$COMPOSER_FILE" ]; then
        echo "   > Correzione composer.json: iniezione name/autoload"
        jq --arg name "$PACKAGE_NAME" '. + {name: $name}' "$COMPOSER_FILE" > temp.json && mv temp.json "$COMPOSER_FILE"

        NAMESPACE_KEY="${CLIENT_NAMESPACE}\\\\"
        jq ".autoload.psr-4 += {\"$NAMESPACE_KEY\": \"lib/\"}" "$COMPOSER_FILE" > temp.json && mv temp.json "$COMPOSER_FILE"
    else
        echo "   > ATTENZIONE: composer.json non trovato in $COMPOSER_FILE"
    fi

    echo "OK: client $API_NAME generato in $WORKING_DIR/$CLIENT_DIR"

done

echo "TUTTI I CLIENT PAGOpa GENERATI"
