(function (send) {
  XMLHttpRequest.prototype.send = function (data) {

    //console.log(
    //   'called injection'
    // )
    try {
      if (data instanceof FormData && data.get('action') === 'qmn_process_quiz') {
        const uidd = data.get('qsm_unique_key');
        //console.log('Intercepted quiz submission, UUID:', uidd);

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
    '.qsm-quiz-container'
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
  //console.log('ajaxCall started', uuiid);

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
    //console.log('ajaxCall response:');
    //console.log(res)

    if (res?.data?.exist) {

      const uniqueQSMId = res.data.uidq;
      const urlR = res.data.link;

      const templateUrl = EupQ_Ajax_Obj.templatesUrl + 'starteupassq.html';
    
      jQuery.get(templateUrl, function(templateHtml) {
        
        const compiledTemplate = _.template(templateHtml);
  
        const rendered_underscore = compiledTemplate({
          qsmid : uniqueQSMId,
          urlR  : urlR
        })

        // const container = document.querySelector(
        //   '.qsm-quiz-container'
        // );
        const container = document.getElementById('main');
        //console.log(container)
        if (container) {
          //console.log('container found:');
          container.innerHTML = rendered_underscore;
        }

      });

    }

}
