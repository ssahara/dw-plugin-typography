DokuWiki plugin Typography
=============================
Extended version from original [Typography plugin](http://treecode.pl/typography.html) developed by Paweł Piekarski.

Sometimes there is need for nice looking slogan, quotation or article paragraph. For full control you need full control over font displaing as CSS can give. Typography plugin extends DokuWiki markup by typesetting abilities. It brings all font related directives from CSS as wiki syntax.

Typography is syntax plugin. Currently it covers all typographic aspects of CSS. 

It gives ability to adjust settings for text look, but does not influence subtle stuff like text baseline or vertical alignment.


## Syntax

`<typo parameters>`beautiful looking test`</typo>` where parameters are semicolon separated `name:value;` 

| functionality  | parameter syntax | comment |
|:--             |:--               |:--      |
|font family     | `<typo ff:Coma separated, font names, 'Single quatation required for those contains spaces';>`Text`</typo>` |  |
|font variant    | `<typo fv:smallcaps;>`Text`</typo>` | only "smallcaps" value allowed |
|font size       | `<typo fs:20px;>`Text`</typo>` | values below zero not allowed |
|line height     | `<typo lh:20px;>`Text`</typo>` | values below zero not allowed |
|letter spacing  | `<typo ls:20px;>`Text`</typo>` | values below zero allowed |
|spacing between word  | `<typo ws:20px;>`Text`</typo>` | values below zero allowed |

## Shorter syntax

The Typography plugin provides additional short (or single property) syntax those are compatible with [fontcolor](https://www.dokuwiki.org/plugin:fontcolor), [fontfamily](https://www.dokuwiki.org/plugin:fontfamily) and [fontsize2](https://www.dokuwiki.org/plugin:fontsize2) plugins. If you have enabled these three plugins, the short syntax are treated by relevant plugins instead of this plugin. These short syntax are available through toolbar icons: ![fontcolor icon](https://raw.githubusercontent.com/ssahara/dw-plugin-typography/master/images/fontcolor/picker.png) ![fontfamily icon](https://raw.githubusercontent.com/ssahara/dw-plugin-typography/master/images/fontfamily/picker.png) ![font-size icon](https://raw.githubusercontent.com/ssahara/dw-plugin-typography/master/images/fontsize/picker.png).

```
  <fc Turquoise>Specific color text</fc>        = <typo fc:Turquoise;>Specific color text</typo>
  <ff 'Comic Sans MS'>Different font used</ff>  = <typo ff:'Comic Sans MS';>Different font used</typo>
  <fs 200%>Large size text</fs>                 = <typo fs:200%;>Large size text</typo>
```

----
Licensed under the GNU Public License (GPL) version 2

see https://www.dokuwiki.org/plugin:typography for more information.
