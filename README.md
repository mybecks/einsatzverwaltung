Wordpress Einsatz Plugin der FF Bad Schönborn
=============================================

# How to develop

* Start Docker Wordpress Installation

    `docker-compose up`
* Shutdown Docker

    `docker-compose down -v`
* Shutdown Docker and remove database

    `docker-compose down -v && rm -rf .db_data`
* Execute Bash commands inside Docker wordpress container

    `docker-compose exec wordpress bash`

# How to enable the plugin

After activation you can use the shortcode on any page or post

```
[einsatzverwaltung]
```

