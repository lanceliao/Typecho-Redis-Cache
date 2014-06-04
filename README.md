##Typecho Redis Cache	

Typecho Redis Cache is a full-site cache script for [```Typecho Blogging Platform```](https://github.com/typecho/typecho), inspired by Jim Westergren & Jeedo Aquino's [wordpress-with-redis-as-a-frontend-cache](http://www.jimwestergren.com/wordpress-with-redis-as-a-frontend-cache/).

###Requirements

- PHP >= 5.3
- Redis
- Nginx or Apache server
- Typecho

###Get Started
1. Typecho Redis Cache use [```Credis```](https://github.com/colinmollenhour/credis) as Redis client, so firstly download ```Credis``` [here](https://github.com/colinmollenhour/credis), then upload Credis to Typecho root directory.
2. Rename ```index.php``` in Typecho root directory to ```index_orgin.php```.
3. Configure Redis server host, server port and userkey of ```index.php```, then upload it to Typecho root directory.

The structure of Typecho root directory:
```
├── index.php 			    Typecho Redis Cache script          
├── index_origin.php 	original Typecho index.php
├── Credis 					Credis library
    ├── Client.php 			Credis library files
    ├── other files
├── other directories and files of Typecho
```
Now open the site and enjoying the rapid speed!

###Debug
To view the debug message, please set url query string ```debug``` to ```true``` when visiting a page. example:
```
http://www.example.com/page.html?debug=true
```

###Cache Manage
The cache content will never expire itself, it need to be purged at regular intervals manually.
Set url query string ```userkey``` and ```action``` to purge the cache.

examples:

purge a page cache
```
http://www.example.com/page.html?debug=true&userkey=abc123&action=purgepage
```

purge the whole site chche
```
http://www.example.com/page.html?debug=true&userkey=abc123&action=purgeall
```

###Author

- [@lanceliao](https://github.com/lanceliao)
- [http://www.shuyz.com](http://www.shuyz.com)

###License
Copyright (C) <2014>  <Lance Liao>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.