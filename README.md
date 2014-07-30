DokuWiki plugin Typography
=============================
Extended version from original [Typography plugin](http://treecode.pl/typography.html) developed by Paweł Piekarski.

Sometimes there is need for nice looking slogan, quotation or article paragraph. For full control you need full control over font displaing as CSS can give. Typography plugin extends DokuWiki markup by typesetting abilities. It brings all font related directives from CSS as wiki syntax.

Typography is syntax plugin. Currently it covers all typographic aspects of CSS. 

It gives ability to adjust settings for text look, but does not influence subtle stuff like text baseline or vertical alignment.


Syntax
------

`<typo parameters>`beautiful looking test`</typo>` where parameters are semicolon separated `name:value;` 

| functionality  | parameter syntax | comment |
|:--             |:--               |:--      |
|font family     | `<typo ff:Coma separated, font names, 'Single quatation required for those contains spaces';>`Text`</typo>` |  |
|font variant    | `<typo fv:smallcaps;>`Text`</typo>` | only "smallcaps" value allowed |
|font size       | `<typo fs:20px;>`Text`</typo>` | values below zero not allowed |
|line height     | `<typo lh:20px;>`Text`</typo>` | values below zero not allowed |
|letter spacing  | `<typo ls:20px;>`Text`</typo>` | values below zero allowed |
|spacing between word  | `<typo ws:20px;>`Text`</typo>` | values below zero allowed |


----
Licensed under the GNU Public License (GPL) version 2

see https://www.dokuwiki.org/plugin:typography for more information.
