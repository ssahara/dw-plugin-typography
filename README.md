DokuWiki plugin Typography
=============================
Extended version from original [Typography plugin](http://treecode.pl/typography.html) developed by Paweł Piekarski.

Typography plugin extends DokuWiki markup by typesetting abilities. The `<typo>` markup tag specifies CSS font properties such as font face, size, weight, and color of text.
The parameter consists of CSS property-value pairs (`property: value;`), each pair must be separated by semicolon (`;`) however last one may be omitted. You can use abbreviated **short name** instead of full property name.

Some specific short name are also available as markup tag; `<ff>` (font familiy/name), `<fc>` (color), `<fs>` (size), `<fw>` (weight). First three of them are compatible with [fontfamily](https://www.dokuwiki.org/plugin:fontfamily), [fontcolor](https://www.dokuwiki.org/plugin:fontcolor), and [fontsize2](https://www.dokuwiki.org/plugin:fontsize2) plugins respectively. These short syntax are available through toolbar icons: ![fontcolor icon](https://raw.githubusercontent.com/ssahara/dw-plugin-typography/master/images/fontcolor/picker.png) ![fontfamily icon](https://raw.githubusercontent.com/ssahara/dw-plugin-typography/master/images/fontfamily/picker.png) ![font-size icon](https://raw.githubusercontent.com/ssahara/dw-plugin-typography/master/images/fontsize/picker.png).


| short name | css property name | description |
|:--         |:--                |:--          |
|  `fc`  | color             | color of text |
|  `bg`  | background-color  | background color of text |
|  `fs`  | font-size         | font size of text (large or small) |
|  `fw`  | font-weight       | weight of a font (thick or thin characters in text) |
|  `fv`  | font-variant      | display text in a small-caps font |
|  `ff`  | font-family       | font family for text, must be single quoted if a font name contains white-space |
|  `lh`  | line-height       | space between the lines |
|  `ls`  | letter-spacing    | an extra space between characters  (in px, em, etc) |
|  `ws`  | word-spacing      | an additional space between words (in px, em, etc) |
|  `sp`  | white-space       | specifies how white-space is handled (preserve or collapse) |
|  `va`  | vertical-align    | sets the vertical alignment |

Sometimes, inline styles are necessary when you are building a page by hand. You should however avoid them whenever possible for "semantic markup", better maintainability, and reusability. The [wrap plugin](https://www.dokuwiki.org/plugin:wrap) will provide most powerful and flexible method for specifying a class attribute.


Syntax / Usage
------

### Single parameter example:

```
<typo font-size:large;>Large</typo>, 
<typo fs:x-large>Very large</typo>, 
<fs:xx-large>Huge</fs>, and 
<fs smaller>smaller</fs> size text
```

### Multiple parameter example:

```
<typo fs:larger; fw:bold; ff:serif>Bold serif</typo>, 
<fs:large; fv:small-caps>Small-caps</fs> text
```

### Nesting syntax:

```
<ff:'Georgia', 'MS Serif', serif><fs:36px; lh:1.1>
There is nothing either good or bad, \\ but thinking makes it so.
</fs>\\
<fs:smaller;>//-- William Shakespeare, “Hamlet”, Act 2 scene 2//</fs></ff>
```


----
Licensed under the GNU Public License (GPL) version 2

see https://www.dokuwiki.org/plugin:typography for more information.
