# Nginx 1.27 con OpenSSL para generar el certificado autofirmado de desarrollo (D73)
FROM nginx:1.27-alpine
RUN apk add --no-cache openssl
COPY docker/nginx/10-certificado.sh /docker-entrypoint.d/10-certificado.sh
RUN chmod +x /docker-entrypoint.d/10-certificado.sh
