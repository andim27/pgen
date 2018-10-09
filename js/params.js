$(document).ready(function() {
 state='run';
 protocol_params='direct';
 win = document.getElementsByTagName('iframe')[0].contentWindow;
 fr = document.getElementsByTagName('iframe')[0];
 if (fr.src.search('generator')>0) {
     protocol_params='frame';
 }
 url=window.location.href+'params/params.json';
 window.setInterval(function(){
   try {    
        if (state != 'stop') {
           $.getJSON(url+'?t='+new Date().getTime(), function(params_data) {        
  	           if (protocol_params == 'frame') {
                   win.postMessage(params_data, "*");
               } else {
                   window.onmessage({data:params_data});
               }

                if (params_data.hasOwnProperty('state')) {state=params_data.state;}
           });
        }
       } catch (ex) {console.error('Error get params:',ex.message); state='stop';}
    }, 5000);
});
