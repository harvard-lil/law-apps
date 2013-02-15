#Law Apps

Improving the library e-resources interface, starting with the Law School

## Installation

### elasticsearch

Law Apps uses elasticsearch to index library e-resources items.

Download it and install it:

    http://www.elasticsearch.org/

### PHP and the web server

Law Apps Box is written in PHP. PHP 5.3 or later is recommended.

Serving up ShelfLife in [Apache](http://httpd.apache.org/) is probably the easiest way to get started. ShelfLife relies on rewrite rules in .htaccess. Be sure you're allowing for .htaccess in your httpd configuration and that you have mod_php and mod_rewrite installed.

### wkhtmltopdf

[wkhtml2pdf](http://madalgo.au.dk/~jakobt/wkhtmltoxdoc/wkhtmltopdf-0.9.9-doc.html) helps us build thumbnails of our e-resources.

If you're on Redhat/CentOS, you can yum install wkhtmltopdf [using this method](http://blog.dakdad.com/post/13145939686/install-wkhtmltopdf-centos-5)

### ImageMagick and the convert command line utility

[ImageMagick](http://www.imagemagick.org/script/index.php) also helps us build thumbnails of our e-resources.

ImageMagick is pretty common and is likely available as a package for your system.

### Getting the source

Head on over to your web document root (in our Apache instance, we use /var/www/html) and use the git clone command to get the latest version of Law Apps:

    cd /var/www/html
    git clone https://github.com/harvard-lil/las-apps.git

### Configure

You should now have Law Apps installed. Let's configure it.

    cd /var/www/html/law-apps/etc
    cp master.ini.example to master.ini

Edit the master.cfg config file with the keys and paths that we've created in the instructions above.

### Setup .htaccess

We use a .htaccess to route requests in the Law Apps interface. An example is supplied, just copy it.

    cd /var/www/html/awesome/
    cp .htaccess.example .htaccess

### Success

If things are working correctly you should be able see Law Apps at http://yourhost/awesome/