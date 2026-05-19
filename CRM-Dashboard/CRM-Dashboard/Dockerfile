FROM node:20-bookworm-slim

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends python3 make g++ \
    && rm -rf /var/lib/apt/lists/*

COPY package*.json ./
RUN npm_config_build_from_source=true npm ci --omit=dev \
    && npm rebuild sqlite3 --build-from-source

COPY . .

RUN chmod +x ./scripts/run.sh ./scripts/sync-loop.sh

ENV NODE_ENV=production \
    HOST=0.0.0.0 \
    PORT=3000 \
    CRM_DB_DIR=/app/data \
    DATA_CACHE_TTL_MS=15000

EXPOSE 3000

CMD ["./scripts/run.sh"]
