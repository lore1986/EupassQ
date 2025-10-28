
(function (send) {
  XMLHttpRequest.prototype.send = function (data) {

    try {
      if (data instanceof FormData && data.get('action') === 'qmn_process_quiz') {
        const uidd = data.get('qsm_unique_key');

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

  showSpinner("Loading next section...");

  if (hide) {
    container.hidden = true;
    container.innerHTML = '';

  } else {
    
    container.hidden = false;
    hideSpinner();
  }
}


async function ajaxCall(uuiid) {

  const newData = new FormData();
  newData.set('uuid', uuiid);
  newData.set('action', 'eupassq_anubi_qsm');
  newData.set('intercepted', 'true');

  const response = await fetch(EupQ_Ajax_Obj.ajaxUrl, {
    method: 'POST',
    body: newData
  });

  const res = await response.json();
  hideSpinner();
  
  if (res?.data?.exist) {
    
    const urlR = res.data.link;

    const header = res.data.header;
    const section_overview = res.data.section_overview;
    const recording_demo = res.data.recording_demo;
    const results_info = res.data.results_info;
    const closing_message = res.data.closing_message;

    const templateUrl = EupQ_Ajax_Obj.templatesUrl + 'starteupassq.html';
  
    jQuery.get(templateUrl, function(templateHtml) {
      
      const compiledTemplate = _.template(templateHtml);

      const rendered_underscore = compiledTemplate({
        urlR: urlR,
        header: header,
        section_overview: section_overview,
        recording_demo: recording_demo,
        results_info: results_info,
        closing_message: closing_message
      })

      const container = document.getElementById('main');
      
      if (container) {
        container.innerHTML = rendered_underscore;
      }

    });

  }

}
