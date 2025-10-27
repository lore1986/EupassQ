// ==========================
//  AUDIO RECORDING HANDLER
// ==========================

var activeMediaStreamRecorder = null;
var arrayBlobs = [];
var activeTimerInterval = null;
var maxRecordingTime = 60; // seconds

document.addEventListener('DOMContentLoaded', function () {
    const audioq = document.querySelectorAll("[data-euqtpe='audio']");
    audioq.forEach(aq => Attach_Audio_Event(aq));
    setInterval(EupassQ_scan_for_audio, 500);
});

// ==========================
//  UTILITY FUNCTIONS
// ==========================


function EupassQ_scan_for_audio() {
    const audioPl = document.querySelectorAll('.audio-playback');
    if (audioPl.length >= 1) {
        audioPl.forEach(element => {
            const srcAtt = element.getAttribute('src');
            const parentN = element.parentNode;
            const audioCheck = parentN.querySelector('.audio-status');
            if (srcAtt != null) {
                element.hidden = false;
                audioCheck.hidden = false;
            } else {
                element.hidden = true;
                audioCheck.hidden = true;
            }
        });
    }
}

function On_Error_Recording(ev) {
    console.error('Recording error:', ev);
}

function On_Start_Recording(container) {
    let timeLeft = maxRecordingTime;
    const timerDisplay = container.querySelector('.timer-display');

    if (timerDisplay) {
        timerDisplay.textContent = `Recording... ${timeLeft}s left`;
        timerDisplay.hidden = false;
    }

    if (activeTimerInterval) clearInterval(activeTimerInterval);

    activeTimerInterval = setInterval(() => {
        timeLeft--;
        if (timerDisplay) timerDisplay.textContent = `Recording... ${timeLeft}s left`;

        if (timeLeft <= 0) {
            clearInterval(activeTimerInterval);
        }
    }, 1000);
}

async function On_Stop_Recording(ev, timerec, chunks, arrB) {
    activeMediaStreamRecorder = null;
    clearTimeout(timerec);

    if (activeTimerInterval) clearInterval(activeTimerInterval);

    const cont = arrB[0];
    const timerDisplay = cont.querySelector('.timer-display');
    if (timerDisplay) {
        timerDisplay.textContent = "";
        timerDisplay.hidden = true;
    }

    let ind = cont.getAttribute('data-index');
    let idd = cont.getAttribute('data-euid');

    console.log(idd)


    const audioBlob = new Blob(chunks, { type: 'audio/webm' });
    const audioURL = URL.createObjectURL(audioBlob);

    isVoiceTooLow(audioBlob, -22).then(tooLow => {
        if (tooLow) {
            const errM = document.getElementById('error_' + idd);
            errM.innerText = "Please speak louder. Volume of your recording is too low, therefore it cannot be saved. Please try again.";
            errM.hidden = false;

            URL.revokeObjectURL(audioURL);
            chunks = [];

            arrB[1].disabled = false;
            arrB[2].disabled = true;
        } else {
            const blobObj = {
                index: ind,
                idq: idd,
                blob: audioBlob,
                url: audioURL
            };

            arrayBlobs.push(blobObj);

            console.log(arrayBlobs)

            const audioPlayback = cont.querySelector('.audio-playback');
            if (audioPlayback) {
                audioPlayback.src = audioURL;
                audioPlayback.hidden = false;
            }

            arrB[1].disabled = false;
            arrB[2].disabled = true;
        }
    });
}

function Attach_Audio_Event(container) {
    let mediaRecorder;
    let audioChunks = [];
    let recordingTimeout;

    const buttonStart = container.querySelector('.start-record');
    const buttonEnd = container.querySelector('.stop-record');
    const divElementsArr = [container, buttonStart, buttonEnd];

    buttonEnd.addEventListener('click', function () {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
    });

    buttonStart.addEventListener('click', function () {
        // ✅ Prevent multiple recordings
        if (activeMediaStreamRecorder !== null) {
            const errMsg = container.querySelector('.recording-warning');
            if (errMsg) {
                errMsg.textContent = "Please stop the current recording before starting a new one.";
                errMsg.hidden = false;
            } else {
                alert("Please stop the current recording before starting a new one.");
            }
            return;
        }

        const cont_index = container.getAttribute('data-index');
        const idd = container.getAttribute('data-euid');
        const errM = document.getElementById('error_' + idd);
        errM.innerText = "";
        errM.hidden = true;

        const warn = container.querySelector('.recording-warning');
        if (warn) warn.hidden = true;

        // Remove any existing recording for this question
        const index = arrayBlobs.findIndex(bl => bl.index == cont_index);
        if (index != -1) {
            URL.revokeObjectURL(arrayBlobs[index].url);
            arrayBlobs.splice(index, 1);
            const audioPlayback = container.querySelector('.audio-playback');
            if (audioPlayback) {
                audioPlayback.removeAttribute('src');
                container.querySelector('.audio-status').hidden = true;
            }
        }

        audioChunks = [];

        navigator.mediaDevices.getUserMedia({
            audio: {
                mandatory: {
                    googEchoCancellation: "false",
                    googAutoGainControl: "false",
                    googNoiseSuppression: "false",
                    googHighpassFilter: "false"
                },
                optional: []
            },
        }).then(stream => {
            const options = {
                audioBitsPerSecond: 128000,
                mimeType: 'audio/webm'
            };

            mediaRecorder = new MediaRecorder(stream, options);
            activeMediaStreamRecorder = mediaRecorder; // mark as active

            mediaRecorder.ondataavailable = (event) => audioChunks.push(event.data);
            mediaRecorder.onstop = (event) => {
                On_Stop_Recording(event, recordingTimeout, audioChunks, divElementsArr);
                activeMediaStreamRecorder = null; // reset
            };
            mediaRecorder.onerror = (event) => On_Error_Recording(event);
            mediaRecorder.onstart = () => On_Start_Recording(container);

            // Auto-stop after 60 seconds
            recordingTimeout = setTimeout(() => {
                if (mediaRecorder && mediaRecorder.state === "recording") mediaRecorder.stop();
            }, maxRecordingTime * 1000);

            mediaRecorder.start();

            buttonStart.disabled = true;
            buttonEnd.disabled = false;
        }).catch(err => {
            console.error("Microphone access denied:", err);
            alert("Unable to access your microphone. Please check browser permissions.");
        });
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

async function SubmitMyQuiz() {

    const form = document.getElementById('eupassq_quiz_form');
    if (!form) return;

    showSpinner("Stiamo rielaborando le vostre risposte, grazie per l'attesa.");

    const formData = new FormData(form); 
    const arrQue = document.querySelectorAll('.eupassq-question');
    arrQue.forEach(container => {
        if(container.getAttribute('data-euqtpe') == 'audio') {
            let ind = container.getAttribute('data-index');
            const index = arrayBlobs.findIndex(bl => bl.index == ind);
            
            if(index !== -1) {
                
                const bobj = arrayBlobs[index];
                console.log(bobj)
                let fieldName = 'audio_' + bobj.index + '_' + bobj.idq;
                console.log(fieldName)
                formData.append(fieldName, arrayBlobs[index].blob, `answer_${Date.now()}.webm`);
            }
        }
    });

    console.log(arrQue);


    formData.append('action', 'eupass_qform_submit');
    formData.append('eupassqnc', EupQ_Ajax_Obj.nonce.quiz_out);

    fetch(EupQ_Ajax_Obj.ajaxUrl, {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        //hideSpinner();
        window.location.href = data.data.redirect;
    })
    .catch(err => {
        hideSpinner();
        const responseContainer = document.getElementById('rq-response');
        if (responseContainer) {
            responseContainer.innerHTML = `<span style="color:red;">Submission failed</span>`;
        }
        console.error(err);
    });
}

// ======================
// Spinner Calls 
// ======================
function showSpinner(message = "Loading...") {
  let spinner = document.getElementById('quiz-spinner');

  if (!spinner) {
    spinner = document.createElement('div');
    spinner.id = 'quiz-spinner';
    spinner.innerHTML = `
      <div class="quiz-spinner-overlay">
        <div class="quiz-spinner-box">
          <div class="quiz-spinner-loader"></div>
          <p class="quiz-spinner-message"></p>
        </div>
      </div>
    `;
    document.body.appendChild(spinner);
  }

  spinner.querySelector('.quiz-spinner-message').textContent = message;
  spinner.style.display = 'flex';
}

function hideSpinner() {
  const spinner = document.getElementById('quiz-spinner');
  if (spinner) spinner.style.display = 'none';
}