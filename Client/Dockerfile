# Stage 1: Builder avec Node et dépendances
FROM node:22-alpine AS builder

WORKDIR /app

# Copier les fichiers de dépendances
COPY package.json package-lock.json ./

# Installer les dépendances
RUN npm ci

# Stage 2: Development/Production Runtime
FROM node:22-alpine

WORKDIR /app

# Installer les outils utiles
RUN apk add --no-cache \
    curl \
    git

# Copier les dépendances du builder
COPY --from=builder /app/node_modules /app/node_modules

# Copier l'application
COPY . .

# Exposer le port Vite
EXPOSE 5173

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:5173/ || exit 1

# Par défaut, lancer le serveur de développement
CMD ["npm", "run", "dev"]
