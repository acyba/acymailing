const acym_helperIntroJS = {
    introContent: [],
    setIntrojs: function (where, forceDisplay) {
        forceDisplay = forceDisplay === undefined ? false : forceDisplay;
        let urlGetIntroDB = ACYM_TOGGLE_URL + '&task=getIntroJSConfig';
        jQuery.post(urlGetIntroDB, function (display) {
            display = JSON.parse(display);
            if ((display[where] === undefined || display[where] == 0 || forceDisplay) && !jQuery('.introjs-tooltip').is(':visible')) {
                let tmpIntro = acym_helperIntroJS.introContent;
                let optionsIntro = [];
                for (let i = 0 ; i < tmpIntro.length ; i++) {
                    jQuery('' + tmpIntro[i].element).attr('data-intro', tmpIntro.text);
                    optionsIntro.push({
                        element: document.querySelector('' + tmpIntro[i].element),
                        intro: tmpIntro[i].text,
                        position: tmpIntro[i].position === undefined ? 'bottom' : tmpIntro[i].position
                    });
                }
                let intro = introJs();
                intro.setOptions({
                    steps: optionsIntro
                });
                intro.start();
                jQuery('.introjs-nextbutton').html(ACYM_JS_TXT.ACYM_NEXT);
                jQuery('.introjs-prevbutton').html(ACYM_JS_TXT.ACYM_BACK);
                jQuery('.introjs-skipbutton').html(ACYM_JS_TXT.ACYM_SKIP);
                let url = ACYM_TOGGLE_URL + '&task=toggleIntroJS&where=' + where;
                jQuery.post(url, function (response) {
                });
            }
        });
    }
};
