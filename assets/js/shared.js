(function(send) {
  XMLHttpRequest.prototype.send = function(data) {
    var id = 0;
    var uidd = 0;
    var sent = 0;
    try {
      if (data instanceof FormData && data.get('action') === 'qmn_process_quiz') {
        
        console.log(data);
        console.log(data.get('qmn_quiz_id'));
        id = data.get('qmn_quiz_id');
        uidd = data.get('qsm_unique_key');
        console.log('id ' + id);
        
        sent = 1;
      }
    } catch (err) {
      console.error('Intercept error:', err);
    }

    // Continue with normal request
    send.call(this, data);

    if(sent > 0)
    {
        ajaxCall(id, uidd);
    }
  };
})(XMLHttpRequest.prototype.send);


function ajaxCall(iid, uuiid)
{
    console.log(iid)
    const newData = new FormData();
    newData.set('id', iid);
    newData.set('uuid', uuiid)

    console.log("called")
    console.log(EupQ_Ajax_Obj.ajaxUrl)

    // Modify anything you need
    newData.set('action', 'eupassq_anubi_qsm'); // your PHP handler
    newData.set('intercepted', 'true');

    fetch(EupQ_Ajax_Obj.ajaxUrl, {
        method: 'POST',
        body: newData
    })
        .then(r => r.text())
        .then(response => {
            
            const res = JSON.parse(response);

            setTimeout(function() {
                const btn = document.getElementById('qsm_retake_button');
                console.log("res " + res)
                console.log("res data " + res.data)
                console.log(btn)
                btn.setAttribute('data-id',res.data.idq);
                btn.setAttribute('data-unid',res.data.uidq);
                btn.setAttribute('type', 'button')
                console.log(btn)
            }, 500);
            
        })
        .catch(console.error);
}