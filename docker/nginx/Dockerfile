FROM nginx:latest

# Nginx設定ファイルをコピー
COPY default.conf /etc/nginx/conf.d/default.conf

# ポート80を公開
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
