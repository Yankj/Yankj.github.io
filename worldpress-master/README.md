# Worldpress 

A concise, minimalist and mobile-friendly Wordpress theme dedicated to people who love blogging. Based on bootstrap framework,built in HTML5 and CSS3.

## Features:
* No depedence on parent theme
* Built-in customized version of Bootstrap resources
* Four widget area, one in right sidebar and three optional in footer area. 
* Media gallery widget included to display thumbnail of recent uploaded images. 
* Featured post type, which will be promoted to front page carousel when posted
* Built-in support for like/dislike feature of the post 
* No images included, only CSS3 font-icon
* Less files included for easily changing the color and appearance via reset variable

## How to use
Using git:
```shell
cd your-wordpress-root/wp-content/themes
git clone https://github.com/frankdai/worldpress.git 
```
Or download the [master zip files](https://github.com/frankdai/worldpress/archive/master.zip) and upload to your server directory.

## Customization
Please build your child theme according to instruction on [Wordpress Codex](https://codex.wordpress.org/Child_Themes)

You can also use the files in the /less folder to edit the color,font variables. After editing, please using grunt for compiling (nodejs required)

```shell
npm install
grunt
```

## License
MIT License. 



