Wordpress Einsatz Plugin der FF Bad Schönborn
=============================================

# Misc
http://wordpress.org/extend/plugins/rewrite-rules-inspector/
Goal rewrite /mission/2015/12/2015_12_brandmeldealarm to /mission/2015/12/brandmeldealarm(-1)

Rewrites via nginx
http://nginx.org/en/docs/http/ngx_http_rewrite_module.html#rewrite
https://www.nginx.com/blog/creating-nginx-rewrite-rules/


<pre>
(/mission/\d{4}/\d{2}/).*_(\D+[a-zA-Z])

(/mission/.*)_(\D+[a-zA-Z])


rewrite ^(/mission/.*)_(\D+[a-zA-Z]) $1$2 last;
rewrite ^(/mission/\d{4}/\d{2}/).*_(\D+[a-zA-Z]) $1$2 last;
</pre>
