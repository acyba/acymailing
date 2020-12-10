const acym_editorWysidOutlook = {
    setButtonOutlook: function ($element) {
        let borderRadius = 0;
        if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
            borderRadius = parseInt($element.css('borderBottomLeftRadius').replace(/[^-\d\.]/g, ''));
        } else {
            borderRadius = parseInt($element.css('borderRadius').replace(/[^-\d\.]/g, ''));
        }
        let borderColor = $element.css('border-color');
        let backgroundColor = $element.css('background-color');
        let href = $element.attr('href');
        let widthButton = $element.outerWidth();
        let cssRoundrect = 'style = "width: ' + widthButton + '; height:' + $element.css('height').replace(/[^-\d\.]/g, '') + '"';
        let css = 'color :' + $element.css('color') + '; font-family:' + $element.css('font-family') + '; font-size:' + $element.css('font-size') + ';';
        let text = $element.html();
        //We can also call it shitty button
        let outlookButton = '<!--[if mso]><v:roundrect '
                            + cssRoundrect
                            + ' xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'
                            + href
                            + '" style="v-text-anchor:middle;" arcsize="'
                            + borderRadius
                            + '%" strokecolor="'
                            + borderColor
                            + '" fillcolor="'
                            + backgroundColor
                            + '"><w:anchorlock/><center style="'
                            + css
                            + '">'
                            + text
                            + '</center></v:roundrect><![endif]--><!--[if !mso]> -->';
        return outlookButton;
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
