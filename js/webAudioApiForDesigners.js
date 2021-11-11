if(typeof(webkitAudioContext)=="undefined"&&typeof(mozAudioContext)=="undefined"){window.webkitAudioContext=function(){throw "Web Audio API not supported in this browser";};}
function initializeNewWebAudioContext(){var context;try{if(typeof(mozAudioContext)!="undefined"){context=new mozAudioContext();}
else{context=new webkitAudioContext();}}
catch(e){
    /* alert('Web Audio API is not supported in this browser.  HTML 5 Audio Elements will be used instead.'); */
    context=new fallbackAudioContext();}
return context;}
webkitAudioContext.prototype.loadSound=function(url,strNameOfSoundBufferVariable){var context=this;var request=new XMLHttpRequest();request.open('GET',url,true);request.responseType='arraybuffer';request.onload=function(){context.decodeAudioData(request.response,function(buffer){context.buffers[strNameOfSoundBufferVariable]=buffer;},onError);}
request.send();}
function onError(){alert('something suboptimal happened while attempting to decode some audioData.');}
webkitAudioContext.prototype.playSound=function(strBuffer){var context=this;buffer=this.buffers[strBuffer];var source=context.createBufferSource();source.buffer=buffer;source.connect(context.destination);source.noteOn(0);}
webkitAudioContext.prototype.buffers={};function fallbackAudioContext(){this.buffers={};}
function fallbackAudioEntity(url){this.audioElement=new Audio(url);this.tracks={};this.audioBufferIndex=0;this.maxSoundsAtOnce=32;}
fallbackAudioEntity.prototype.playNew=function(){var i=this.audioBufferIndex;if(typeof(this.tracks[i])!='undefined')
this.tracks[i].src='';this.tracks[i]=this.audioElement.cloneNode(true);this.tracks[i].play();this.audioBufferIndex++;if(this.audioBufferIndex>=this.maxSoundsAtOnce)
this.audioBufferIndex=0;}
fallbackAudioContext.prototype.loadSound=function(url,strNameOfSoundBufferVariable){this.buffers[strNameOfSoundBufferVariable]=new fallbackAudioEntity(url);}
fallbackAudioContext.prototype.playSound=function(strBufferName){this.buffers[strBufferName].playNew();}