var activeMediaStreamRecorder = null;
var arrayBlobs = [];



document.addEventListener('DOMContentLoaded', function(){

    const audioq = document.querySelectorAll("[data-euqtpe='audio']");

    //console.log(EupQ_Ajax_Obj)
   
    audioq.forEach(aq => {
        Attach_Audio_Event(aq)
    });

    setInterval(EupassQ_scan_for_audio, 500);

})


function EupassQ_scan_for_audio() {

   const audioPl = document.querySelectorAll('.audio-playback');
   
   if(audioPl.length >= 1)
   {
        audioPl.forEach(element => {
            
            const srcAtt = element.getAttribute('src');
            const parentN = element.parentNode;
            const audioCheck = parentN.querySelector('.audio-status');
            
            if(srcAtt != null)
            {   
                audioCheck.hidden = false;
            }else
            {
                audioCheck.hidden = true;
            }

        });
   }
}

// Run `myFunction` every 1000 milliseconds (1 second)

function On_Error_Recording(ev)
{
    //////console.log(ev)
}

function On_Start_Recording(ev)
{
    //////console.log(ev)
}

async function On_Stop_Recording(ev, timerec, chunks, arrB)
{
    activeMediaStreamRecorder = null;

    clearTimeout(timerec); 
    const cont = arrB[0];

    let ind = cont.getAttribute('data-index');
    let idd = cont.getAttribute('data-euid');

    
    const audioBlob = new Blob(chunks, { type: 'audio/webm' }); 
    const audioURL = URL.createObjectURL(audioBlob);
    var low = false;
    //console.log(low)
    
    isVoiceTooLow(audioBlob, -22).then(tooLow => {
        
        if (tooLow) {
            low = true;
            //console.log(low)
            const errM = document.getElementById('error_' + idd);
            errM.innerText = "Please speak louder. Volume of your recording is too low therefore the recording \
            cannot be saved. Please try again, many thanks."
            errM.hidden = false;
            URL.revokeObjectURL(audioURL);
            //this is really important to reset audio
            audioChunks = [];

            arrB[1].disabled = false;
            arrB[2].disabled = true;

        } else
        {
            const blobObj = {
                index : ind,
                idq : idd,
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
        }
    });

};

   

function Attach_Audio_Event(container)
{
    let mediaRecorder;
    let audioChunks = [];
    let recordingTimeout;

    const buttonStart = container.querySelector('.start-record');
    const buttonEnd = container.querySelector('.stop-record');

    const divElementsArr = [container, buttonStart, buttonEnd];

    buttonEnd.addEventListener('click', function(){
        if(mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
    })

    buttonStart.addEventListener('click', function(){
        
        const cont_index = container.getAttribute('data-index');
        const idd = container.getAttribute('data-euid');

        const errM = document.getElementById('error_' + idd);
        errM.innerText = ""
        errM.hidden = true;

        const index = arrayBlobs.findIndex(bl => bl.index == cont_index);

        if(index != -1)
        {
            URL.revokeObjectURL(arrayBlobs[index].url);
            arrayBlobs.splice(index, 1);

            const audioPlayback = container.querySelector('.audio-playback');
            //console.log(audioPlayback)

            if (audioPlayback) {
                
                audioPlayback.removeAttribute('src');
                container.querySelector('.audio-status').hidden = true;
            }

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
            let ind = container.getAttribute('data-index');
            const index = arrayBlobs.findIndex(bl => bl.index == ind);

            if(index !== -1)
            {
                const bobj = arrayBlobs[index]

                let fieldName = 'audio_' + bobj.index + '_' + bobj.idq + '_' + bobj.uid;

                formData.append(fieldName, arrayBlobs[index].blob, `answer_${Date.now()}.webm`);
            }
        }
        
    });

    const formObj = {};
    formData.forEach((v, k) => formObj[k] = v);

    formData.append('action', 'eupass_qform_submit');
     //console.log(EupQ_Ajax_Obj)
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

