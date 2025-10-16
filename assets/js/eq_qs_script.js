var activeMediaStreamRecorder = null;
var arrayBlobs = [];



document.addEventListener('DOMContentLoaded', function(){

    const audioq = document.querySelectorAll("[data-euqtpe='audio']");

    console.log(EupQ_Ajax_Obj)
   
    audioq.forEach(aq => {
        Attach_Audio_Event(aq)
    });

})

function On_Error_Recording(ev)
{
    ////console.log(ev)
}

function On_Start_Recording(ev)
{
    ////console.log(ev)
}

async function On_Stop_Recording(ev, timerec, chunks, arrB)
{
    activeMediaStreamRecorder = null;

    clearTimeout(timerec); 
    
    
    const audioBlob = new Blob(chunks, { type: 'audio/webm' }); 
    const improvedBlob = null;//await improve_audio_with_noise_reduction(audioBlob);
    const audioURL = URL.createObjectURL(audioBlob);

    // isVoiceTooLow(improvedBlob, -22).then(tooLow => {
    //     if (tooLow) {
    //         //console.log("Voice is TOO LOW");
    //     } else {
    //         //console.log("Voice level is OK");
    //     }

    //     //console.log("tooLow =", tooLow);
    // });

    const cont = arrB[0];


    let ind = cont.getAttribute('data-index');
    let idd = cont.getAttribute('data-euid');


    //check 
    const blobObj = {
        index : ind,
        idq : idd,
        uid: 3, //fix this with actual userid
        blob: audioBlob,
        url: audioURL
    }

    const objB = new Object(blobObj);
    
    arrayBlobs.push(objB);

    const audioPlayback = cont.querySelector('.audio-playback');

    if (audioPlayback) {
        
        if(audioPlayback.getAttribute('src') == null)
        {
            audioPlayback.setAttribute('src',"")
        } 

        audioPlayback.src = audioURL; //audioURL;
        audioPlayback.style.display = 'block';
    }

    arrB[1].disabled = false;
    arrB[2].disabled = true;

};

   

function Attach_Audio_Event(container)
{
    let mediaRecorder;
    let audioChunks = [];
    let recordingTimeout;

    const buttonStart = container.querySelector('.start-record');
    const buttonEnd = container.querySelector('.stop-record');
    const buttonReRecord = container.querySelector('.re-record')

    const divElementsArr = [container, buttonStart, buttonEnd];

    buttonEnd.addEventListener('click', function(){
        if(mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
    })

    buttonStart.addEventListener('click', function(){
        
        const cont_index = container.getAttribute('data-index');

        const index = arrayBlobs.findIndex(bl => bl.index == cont_index);

        if(index != -1)
        {
            URL.revokeObjectURL(arrayBlobs[index].url);
            arrayBlobs.splice(index, 1);
        }
    
        //this is really important to reset audio
        audioChunks = [];


        if(activeMediaStreamRecorder == null)
        {
            navigator.mediaDevices.getUserMedia(
                { 
                    // audio: true
                    "audio": {
                        "mandatory": {
                            "googEchoCancellation": "false",
                            "googAutoGainControl": "false",
                            "googNoiseSuppression": "false",
                            "googHighpassFilter": "false"
                        },
                        "optional": []
                    },
                }
            ).then(stream => {

                const options = {
                    audioBitsPerSecond: 128000,
                    mimeType: 'audio/webm'
                }

                mediaRecorder = new MediaRecorder(stream, options);
                mediaRecorder.ondataavailable = (event) => audioChunks.push(event.data);
                mediaRecorder.onstop = (event) => On_Stop_Recording(event, recordingTimeout, audioChunks, divElementsArr);
                mediaRecorder.onerror = (event) => On_Error_Recording(event);
                mediaRecorder.onstart = (event) => On_Start_Recording(event);

                recordingTimeout = setTimeout(() => {
                    if(mediaRecorder && mediaRecorder.state === "recording") mediaRecorder.stop();
                }, 60000);

                mediaRecorder.start();
                activeMediaStreamRecorder = mediaRecorder; 
                
                buttonStart.disabled = true;
                buttonEnd.disabled = false;


            })
        }
        
    })
}

async function SubmitMyQuiz() {

    const form = document.getElementById('eupassq_quiz_form');

    //add form validation

    ///

    if (!form) return;

    const formData = new FormData(form); 
    const arrQue = document.querySelectorAll('.eupassq-question');
    arrQue.forEach(container => {

        if(container.getAttribute('data-euqtpe') == 'audio')
        {
            console.log("AUDIO")
            console.log(arrayBlobs)

            let ind = container.getAttribute('data-index');
            const index = arrayBlobs.findIndex(bl => bl.index == ind);
            const bobj = arrayBlobs[index]

            let fieldName = 'audio_' + bobj.index + '_' + bobj.idq + '_' + bobj.uid;

            formData.append(fieldName, arrayBlobs[index].blob, `answer_${Date.now()}.webm`);
        }
        
    });

    const formObj = {};
    formData.forEach((v, k) => formObj[k] = v);

    formData.append('action', 'eupass_qform_submit');
     console.log(EupQ_Ajax_Obj)
    formData.append('eupassqnc', EupQ_Ajax_Obj.nonce.quiz_out);

    fetch(EupQ_Ajax_Obj.ajaxUrl, {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        const resultsContainer = document.getElementById('rq-response');
        window.location.href = data.data.redirect;
        return;
    })
    .catch(err => {
        const responseContainer = document.getElementById('rq-response');
        if (responseContainer) {
            responseContainer.innerHTML = `<span style="color:red;">Submission failed</span>`;
        }
        console.error(err);
    });
}

async function isVoiceTooLow(audioBlob, thresholdDb = -35) {
  
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const arrayBuffer = await audioBlob.arrayBuffer();
    const audioBuffer = await audioCtx.decodeAudioData(arrayBuffer);

    const channelData = audioBuffer.getChannelData(0); 
    const len = channelData.length;


    let sumSquares = 0;
    for (let i = 0; i < len; i++) {
        const sample = channelData[i];
        sumSquares += sample * sample;
    }
    const rms = Math.sqrt(sumSquares / len);
    const db = 20 * Math.log10(rms);

    return db < thresholdDb;
}

async function improve_audio_with_noise_reduction(audioBlob) {
  
    const tempCtx = new AudioContext();
  const arrayBuffer = await audioBlob.arrayBuffer();
  const decodedBuffer = await tempCtx.decodeAudioData(arrayBuffer);


  const offlineCtx = new OfflineAudioContext(
    decodedBuffer.numberOfChannels,
    decodedBuffer.length,
    decodedBuffer.sampleRate
  );

  const source = offlineCtx.createBufferSource();
  source.buffer = decodedBuffer;

  const lowShelf = offlineCtx.createBiquadFilter();
  lowShelf.type = "lowshelf";
  lowShelf.frequency.value = 80;
  lowShelf.gain.value = -20; 

  const highPass = offlineCtx.createBiquadFilter();
  highPass.type = "highpass";
  highPass.frequency.value = 80;

  const presenceBoost = offlineCtx.createBiquadFilter();
  presenceBoost.type = "peaking";
  presenceBoost.frequency.value = 3000;
  presenceBoost.Q.value = 1.5;
  presenceBoost.gain.value = 3;

  const highShelf = offlineCtx.createBiquadFilter();
  highShelf.type = "highshelf";
  highShelf.frequency.value = 6000;
  highShelf.gain.value = 2;

  const noiseGate = offlineCtx.createDynamicsCompressor();
  noiseGate.threshold.value = -30;
  noiseGate.knee.value = 40;
  noiseGate.ratio.value = 2; 
  noiseGate.attack.value = 0.03;
  noiseGate.release.value = 0.025;

  source.connect(lowShelf)
        .connect(highPass)
        .connect(presenceBoost)
        .connect(highShelf)
        .connect(noiseGate)
        .connect(offlineCtx.destination);

  source.start(0);
  const renderedBuffer = await offlineCtx.startRendering();
  const improvedBlob = bufferToWave(renderedBuffer);

  return improvedBlob;
}

//i did not wrote the following function -> it's ai generated i do not know transforming buffer to audio wave.
//it is like a translator for a standard format
function bufferToWave(abuffer) {
  const numOfChan = abuffer.numberOfChannels,
        length = abuffer.length * numOfChan * 2 + 44,
        buffer = new ArrayBuffer(length),
        view = new DataView(buffer),
        channels = [],
        sampleRate = abuffer.sampleRate;

  let offset = 0;
  function writeString(s) { for (let i = 0; i < s.length; i++) view.setUint8(offset + i, s.charCodeAt(i)); }

  // RIFF header
  writeString('RIFF'); offset += 4;
  view.setUint32(offset, 36 + abuffer.length * numOfChan * 2, true); offset += 4;
  writeString('WAVE'); offset += 4;
  writeString('fmt '); offset += 4;
  view.setUint32(offset, 16, true); offset += 4;
  view.setUint16(offset, 1, true); offset += 2;
  view.setUint16(offset, numOfChan, true); offset += 2;
  view.setUint32(offset, sampleRate, true); offset += 4;
  view.setUint32(offset, sampleRate * numOfChan * 2, true); offset += 4;
  view.setUint16(offset, numOfChan * 2, true); offset += 2;
  view.setUint16(offset, 16, true); offset += 2;
  writeString('data'); offset += 4;
  view.setUint32(offset, abuffer.length * numOfChan * 2, true); offset += 4;

  for (let ch = 0; ch < numOfChan; ch++) channels.push(abuffer.getChannelData(ch));
  let sample = 0;
  while (sample < abuffer.length) {
    for (let ch = 0; ch < numOfChan; ch++) {
      const s = Math.max(-1, Math.min(1, channels[ch][sample]));
      view.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
      offset += 2;
    }
    sample++;
  }

  return new Blob([buffer], { type: "audio/wav" });
}
