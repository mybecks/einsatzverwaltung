Einsatzplugin FF Langenbruecken
===============================

~Current Version:0.1.0~


Update all Missions prior to 2016, to be conform with new rewrite rules & displaying:

<pre>
UPDATE wordpress.wp_posts SET post_name = CONCAT(YEAR(post_date), '_', MONTH(post_date), '_', post_name) WHERE post_type = 'mission' AND YEAR(post_date) < 2016
</pre>

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
