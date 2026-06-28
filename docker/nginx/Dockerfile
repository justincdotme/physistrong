FROM nginx:stable-alpine

COPY docker/nginx/http.conf docker/nginx/https.conf /opt/physistrong-nginx/templates/
COPY docker/nginx/start-nginx.sh /opt/physistrong-nginx/start-nginx.sh

RUN chmod +x /opt/physistrong-nginx/start-nginx.sh

CMD ["/opt/physistrong-nginx/start-nginx.sh"]
