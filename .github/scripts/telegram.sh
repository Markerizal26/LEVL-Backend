#!/usr/bin/env bash

BOT_TOKEN="$1"
CHAT_ID="$2"
MESSAGE="$3"

send() {
  curl -s -X POST "https://api.telegram.org/bot${BOT_TOKEN}/sendMessage" \
    -d chat_id="${CHAT_ID}" \
    -d parse_mode="Markdown" \
    -d text="${MESSAGE}" > /dev/null
}

send
