const acym_editorWysidOutlook = {
    setButtonOutlook: function ($element) {
        let borderRadius;
        if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
            borderRadius = $element.css('borderBottomLeftRadius');
        } else {
            borderRadius = $element.css('borderRadius');
        }
        borderRadius = ' arcsize="' + (parseInt(borderRadius.replace(/[^-\d\.]/g, '')) * 2) + '%"';

        let borderColor;
        let borderWidth = $element.css('border-top-width');
        if (borderWidth.indexOf('0') === 0) {
            borderColor = ' strokecolor="' + $element.css('background-color') + '"';
            borderWidth = '0';
        } else {
            borderColor = ' strokecolor="' + $element.css('border-top-color') + '"';
        }
        borderWidth = ' strokeweight="' + borderWidth + '"';

        let backgroundColor = ' fillcolor="' + $element.css('background-color') + '"';
        let href = ' href="' + $element.attr('href') + '"';
        let widthButton = Math.ceil($element.outerWidth()) + 'px';
        let heightButton = $element.css('height').replace(/[^-\d\.]/g, '') + 'px';
        let cssRoundrect = 'style="width: ' + widthButton + '; height:' + heightButton + ';v-text-anchor:middle;"';
        let css = 'color :' + $element.css('color') + '; font-family:' + $element.css('font-family') + '; font-size:' + $element.css('font-size') + ';';
        let text = $element.html();

        return '<!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"'
               + cssRoundrect
               + href
               + borderRadius
               + borderColor
               + borderWidth
               + backgroundColor
               + '><w:anchorlock/><center style="'
               + css
               + '">'
               + text
               + '</center></v:roundrect><![endif]--><!--[if !mso]> -->';
    },
    setBackgroundOutlook: function ($table) {
        let start = '<!--[if gte mso 9]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width: '
                    + $table.width()
                    + 'px;height: '
                    + $table.height()
                    + 'px"><v:fill type="frame" src="'
                    + $table.css('background-image')
                    + '" /><v:textbox inset="0,0,0,0"><![endif]-->';
        let end = '<!--[if gte mso 9]></v:textbox></v:rect><![endif]-->';

        start = start.replace('url("', '').replace('")', '');

        $table.prepend(start).append(end);
    }
};
