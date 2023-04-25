#README

##INTRODUCTION
This module adds a FieldFormatter for an Image field, 
which let's you implement base64 of the image directly.

The code extends the core module image's class ImageFormatter.php,
and add additional functionality into it. Should be stable.

##REQUIREMENTS
The "Image Base64 Formatter" module needs an image field as requirement.

#INSTALLATION
Install via Drupal Administration or with the following composer commands 
in project root folder:
$ composer require drupal/image_base64_formatter
and enable the module in your web folder:
$ drush en image_base64_formatter

##CONFIGURATION
(1)
After installation of this module you'll get a new format type "Image Base64" 
you can assign to any Image Field in a Content Type. Just go to the Type's 
"Manage Display" and choose "Image Base64" instead of "Image" from the Format 
Combo-Box. (see the attachment screenshot)

(2)
Same goes for Image Content Fields in Views, choose "Image Base64" as formatter.

Now the image's base64 encoded in the chosen form will be returned as 
<img src="data:image/jpeg;base64,..."> instead of <img src="path/filename.xxx">.

(3)
Also working well as Restful API (json/xml) service, then the formatter return 
the entire image as base64 encoded and you can directly use outside of your 
decoupled drupal application.

## CHANGELOG
Update Image Base64 Formatter "Manage Display" options:
1. Base64 String: raw base64 encrypted image (string).
2. Image Source: image will be shown as <img src="data:image/jpeg;base64,...">
3. CSS background Source: show the image as url('data:image/jpeg;base64,...')
for CSS background or other related usage.
