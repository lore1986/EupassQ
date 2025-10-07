let activeMediaStreamRecorder = null;

document.addEventListener('DOMContentLoaded', function(){

    const audioq = document.querySelectorAll("[data-euqtpe='audio']");

    audioq.forEach(aq => {
        Attach_Audio_Event(aq)
    });

})

function On_Error_Recording(ev)
{
    //console.log(ev)
}

function On_Start_Recording(ev)
{
    //console.log(ev)
}

function On_Stop_Recording(ev, timerec, chunks, arrB)
{
    activeMediaStreamRecorder = null;

    clearTimeout(timerec); 

    const audioBlob = new Blob(chunks, { type: 'audio/webm' }); 
    const audioURL = URL.createObjectURL(audioBlob);

    const cont = arrB[0];

    const audioPlayback = cont.querySelector('.audio-playback');
    const reRecordBtn = cont.querySelector('.re-record');

    if (audioPlayback) {
        audioPlayback.src = audioURL;
        audioPlayback.style.display = 'block';
    }

    if (reRecordBtn) {
        reRecordBtn.style.display = 'inline-block'; // or 'block' depending on layout
    }


    const reader = new FileReader();
    reader.readAsDataURL(audioBlob);
    reader.onloadend = () => {
        const audioDataInput = cont.querySelector('.audio-data');
        if (audioDataInput) {
            audioDataInput.value = reader.result; 
        }
    };


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
    const divElementsArr = [container, buttonStart, buttonEnd];

    buttonEnd.addEventListener('click', function(){
        if(mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
    })

    buttonStart.addEventListener('click', function(){
        
        if(activeMediaStreamRecorder == null)
        {
            navigator.mediaDevices.getUserMedia(
                { 
                    audio: true
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

//     $(document).on('click', '.re-record', function(){
//         currentContainer = $(this).closest('.eupassq-question');
//         currentContainer.find('.audio-data').val('');
//         currentContainer.find('.audio-playback').attr('src', '').hide();
//         currentContainer.find('.re-record').hide();
//         currentContainer.find('.start-record').click();
//     });

// })(jQuery);

function submit_form_temp() {
    ////console.log("called");

    const form = document.getElementById('eupassq_quiz_form');
    if (!form) return;

    const formData = new FormData(form); 

    //console.log(formData)

    formData.set('action', 'eupass_qform_submit');         
    formData.set('security', form.querySelector('input[name="security"]').value);

    // Convert audio Base64 to Blob
    document.querySelectorAll('.eupassq-question').forEach(container => {
        const audioInput = container.querySelector('.audio-data');
        if (audioInput && audioInput.value) {

           

            const audioBase64 = audioInput.value;
            
            console.log(audioBase64)
            
            const byteString = atob(audioBase64.split(',')[1]);

            console.log(byteString)

            const ab = new ArrayBuffer(byteString.length);

            console.log(ab)


            const ia = new Uint8Array(ab);


            console.log(ia)

            for (let i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }

            console.log(ia)


            const blob = new Blob([ab], { type: 'audio/webm' });

            console.log(blob)


            const fieldName = audioInput.name;
            formData.append(fieldName, blob, `answer_${Date.now()}.webm`);
        }
    });


    

    // Send POST request using fetch
    fetch(EupQ_Ajax_Obj.ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const responseContainer = document.getElementById('rq-response');
        if (responseContainer) {
            responseContainer.innerHTML = `<span style="color:green;">${data.data}</span>`;
        }
    })
    .catch(err => {
        const responseContainer = document.getElementById('rq-response');
        if (responseContainer) {
            responseContainer.innerHTML = `<span style="color:red;">Submission failed</span>`;
        }
        console.error(err);
    });
}

