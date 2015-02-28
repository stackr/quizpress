(function() {  
    tinymce.create('tinymce.plugins.youtube', {  
        init : function(ed, url) {  
            ed.addCommand('insertyoutube', function() {
                        ed.windowManager.open({
                            file : url + '/youtube.html', // file that contains HTML for our modal window
                            width : 320 + parseInt(ed.getLang('button.delta_width', 0)), // size of our window
                            height : 240 + parseInt(ed.getLang('button.delta_height', 0)), // size of our window
                            inline : 1
                        }, {
                            plugin_url : url
                        });
                    });
         
                    // Register buttons
                    ed.addButton('youtube', {title : 'Add a Youtube video', cmd : 'insertyoutube', image: url + '/button-youtube.png' }); 
        }, 
        getInfo : function() {
            return {
                longname : 'Insert Youtube',
                author : 'Stackr Inc.',
                authorurl : 'http://www.stackr.co.kr',
                infourl : 'http://www.stackr.co.kr',
                version : tinymce.majorVersion + "." + tinymce.minorVersion
            };
        },
        createControl : function(n, cm) {  
            return null;  
        },  
    });  
    tinymce.PluginManager.add('youtube', tinymce.plugins.youtube);  
})();
(function() {  
    tinymce.create('tinymce.plugins.graybox', {  
        init : function(ed, url) {  
            ed.addButton('graybox', {  
                title : '회색박스',  
                image : url+'/button-gray.png',  
                onclick : function() {  
                     ed.selection.setContent('<dl><dt>XXX를 먹을 때 고려할 점</dt><dd><ul><li>항목 1</li><li>항목 2</li><li>항목 3</li><li>항목 4</li></ul></dd></dl>');  
  
                }  
            });  
        },  
        createControl : function(n, cm) {  
            return null;  
        },  
    });  
    tinymce.PluginManager.add('graybox', tinymce.plugins.graybox);  
})();