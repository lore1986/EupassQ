(function (send) {
  XMLHttpRequest.prototype.send = function (data) {
    try {
      if (data instanceof FormData && data.get('action') === 'qmn_process_quiz') {
        const uidd = data.get('qsm_unique_key');
        console.log('Intercepted quiz submission, UUID:', uidd);

        hideTill(true);
        send.call(this, data);
        ajaxCall(uidd).then(() => hideTill(false));

        return;
      }
    } catch (err) {
      console.error('Intercept error:', err);
    }

    // fallback
    send.call(this, data);
  };
})(XMLHttpRequest.prototype.send);

function hideTill(hide = true) {
  const container = document.querySelector(
    '.qsm-quiz-container.qsm-quiz-container-2.qmn_quiz_container.mlw_qmn_quiz.quiz_theme_default.qsm-recently-active'
  );

  if (!container) return;

  let spinner = document.getElementById('quiz-loading-spinner');

  if (hide) {
    container.hidden = true;
    container.innerHTML = '';

  
    if (!spinner) {
      spinner = document.createElement('div');
      spinner.id = 'quiz-loading-spinner';
      spinner.innerHTML = `
        <div class="spinner-overlay">
          <div class="spinner"></div>
          <p>Loading next section...</p>
        </div>
      `;
      document.body.appendChild(spinner);
    }
  } else {
    
    container.hidden = false;
    if (spinner) spinner.remove();
  }
}


async function ajaxCall(uuiid) {
  console.log('ajaxCall started', uuiid);

  const newData = new FormData();
  newData.set('uuid', uuiid);
  newData.set('action', 'eupassq_anubi_qsm');
  newData.set('intercepted', 'true');

    const response = await fetch(EupQ_Ajax_Obj.ajaxUrl, {
      method: 'POST',
      body: newData
    });

    const resText = await response.text();
    const res = JSON.parse(resText);
    console.log('ajaxCall response:', res);

    if (res?.data?.exist) {

      const uniqueQSMId = res.data.uidq;
      const urlR = '/europassQ/' + uniqueQSMId;

      const templateUrl = EupQ_Ajax_Obj.templatesUrl + 'starteupassq.html';
    
      jQuery.get(templateUrl, function(templateHtml) {
        
        const compiledTemplate = _.template(templateHtml);
  
        const rendered_underscore = compiledTemplate({
          qsmid : uniqueQSMId,
          urlR  : urlR
        })

        const container = document.querySelector(
          '.qsm-quiz-container.qsm-quiz-container-2.qmn_quiz_container.mlw_qmn_quiz.quiz_theme_default.qsm-recently-active'
        );

        if (container) {
          container.innerHTML = rendered_underscore;
        }

      });

    }

    // if (res?.data?.exist) {
    //   const uniqueQSMId = res.data.uidq;

    //   const oldBtn = document.getElementById('qsm_retake_button');
    //   if (oldBtn) oldBtn.remove();

    //   const newBtn = document.createElement('button');
    //   newBtn.id = 'go_to_next_quiz_button';
    //   newBtn.textContent = 'Go to next quiz section';
    //   newBtn.classList.add('qsm-btn');
    //   newBtn.classList.add('qmn_btn') 


    //   newBtn.addEventListener('click', () => {
    //     window.location.href = '/europassQ/' + uniqueQSMId;
    //   });

    //   const container = document.querySelector(
    //     '.qsm-quiz-container.qsm-quiz-container-2.qmn_quiz_container.mlw_qmn_quiz.quiz_theme_default.qsm-recently-active'
    //   );

    //   if (container) {
    //     container.innerHTML = '';
    //     container.appendChild(newBtn);
    //   }

    //   console.log('New button created for next quiz section.');
    // } 
}
