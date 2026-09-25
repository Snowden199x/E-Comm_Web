(() => {
    'use strict';
    const app=document.getElementById('omoApp'); if(!app)return;
    const el=id=>document.getElementById(id), initial=JSON.parse(el('omoInitial').textContent);
    const state={status:'all',search:'',date_from:'',date_to:'',page:1,...initial.filters};
    let pagination=initial.pagination,currentOrder=null,drawerHtml=null,listSeq=0,drawerSeq=0,saving=false,timer;
    const showError=(message,id='omoError')=>{const node=el(id);node.textContent=message;node.hidden=!message;};
    async function api(url,options={}){
        const response=await fetch(url,{...options,headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,...options.headers}});
        const data=await response.json().catch(()=>({}));
        if(!response.ok)throw new Error(Object.values(data.errors||{})[0]?.[0]||data.message||'Could not load orders. Please try again.');
        return data;
    }
    function sync(counts){
        if(counts)document.querySelectorAll('[data-count]').forEach(node=>node.textContent=Number(counts[node.dataset.count]||0).toLocaleString());
        document.querySelectorAll('#omoTabs .omo-tab,.omo-status-option').forEach(node=>node.classList.toggle(node.classList.contains('omo-tab')?'is-active':'is-selected',node.dataset.status===state.status));
        const labels={all:'All status',new:'New',pack:'To Pack',pickup:'Ready for Pickup',pending:'Pending Delivery',completed:'Delivered / Completed',cancelled:'Cancelled',returned:'Returned'};
        el('omoStatusLabel').textContent=labels[state.status]||labels.all;
        el('omoResultCount').textContent=`Showing ${pagination.from||0}–${pagination.to||0} out of ${pagination.total} entries`;
        el('omoPrevPage').disabled=pagination.page<=1;el('omoNextPage').disabled=pagination.page>=pagination.last;
        const pages=el('omoPagerPages');pages.replaceChildren();
        for(let page=Math.max(1,pagination.page-2);page<=Math.min(pagination.last,pagination.page+2);page++){const button=document.createElement('button');button.type='button';button.textContent=page;button.classList.toggle('is-active',page===pagination.page);if(page===pagination.page)button.setAttribute('aria-current','page');button.addEventListener('click',()=>{state.page=page;load();});pages.append(button);}
        document.querySelectorAll('.omo-view-btn').forEach(node=>node.classList.toggle('is-active',Number(node.dataset.id)===currentOrder));
    }
    async function load(){const seq=++listSeq;try{const params=new URLSearchParams(Object.entries(state).filter(([,v])=>v!==''&&v!=null));const data=await api(`${app.dataset.endpoint}?${params}`);if(seq!==listSeq)return;el('omoTableBody').innerHTML=data.html;pagination=data.pagination;sync(data.counts);showError('');history.replaceState(null,'',`${app.dataset.endpoint}?${params}`);}catch(error){if(seq===listSeq)showError(error.message);}}
    async function openOrder(id){const seq=++drawerSeq;currentOrder=Number(id);el('omoLayout').classList.add('is-open');el('omoDrawer').setAttribute('aria-hidden','false');el('omoDrawer').innerHTML='<div class="omo-drawer__inner"><p class="omo-empty">Loading order…</p></div>';try{const data=await api(`${app.dataset.orderBase}/${id}`);if(seq!==drawerSeq)return;el('omoDrawer').innerHTML=data.html;drawerHtml=data.html;sync();}catch(error){if(seq===drawerSeq){closeOrder();showError(error.message);}}}
    function closeOrder(){if(saving)return;drawerSeq++;currentOrder=null;drawerHtml=null;el('omoLayout').classList.remove('is-open');el('omoDrawer').setAttribute('aria-hidden','true');sync();}
    el('omoTableBody').addEventListener('click',event=>{const btn=event.target.closest('.omo-view-btn');if(btn&&!saving)(currentOrder===Number(btn.dataset.id)?closeOrder():openOrder(btn.dataset.id));});
    el('omoDrawer').addEventListener('click',event=>{if(event.target.closest('#omoDrawerClose'))closeOrder();});
    el('omoDrawer').addEventListener('submit',async event=>{if(event.target.id!=='omoActionForm')return;event.preventDefault();if(saving)return;const form=event.target,action=event.submitter?.value;if(!action)return;const body=Object.fromEntries(new FormData(form));body.action=action;if(action==='decline'&&!body.reason?.trim()){showError('Enter a reason before declining this order.','omoActionError');form.elements.reason.focus();return;}saving=true;showError('','omoActionError');const buttons=[...form.querySelectorAll('button')];buttons.forEach(b=>b.disabled=true);try{await api(form.dataset.url,{method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});await Promise.all([load(),openOrder(currentOrder)]);}catch(error){showError(error.message,'omoActionError');buttons.forEach(b=>b.disabled=false);}finally{saving=false;}});
    function closePanels(){[['omoStatusPanel','omoStatusBtn'],['omoDatePanel','omoDateBtn']].forEach(([panel,button])=>{el(panel).classList.remove('is-open');el(button).setAttribute('aria-expanded','false');});}
    function filter(status){state.status=status||'all';state.page=1;closePanels();sync();load();}
    document.querySelectorAll('#omoTabs .omo-tab,.omo-status-option,[data-filter]').forEach(btn=>btn.addEventListener('click',()=>filter(btn.dataset.status||btn.dataset.filter)));
    el('omoSearchInput').value=state.search;el('omoSearchInput').addEventListener('input',event=>{state.search=event.target.value;state.page=1;clearTimeout(timer);timer=setTimeout(load,250);});
    [['omoStatusPanel','omoStatusBtn'],['omoDatePanel','omoDateBtn']].forEach(([panel,button])=>el(button).addEventListener('click',()=>{const open=!el(panel).classList.contains('is-open');closePanels();el(panel).classList.toggle('is-open',open);el(button).setAttribute('aria-expanded',String(open));}));
    document.addEventListener('click',event=>{if(!event.target.closest('.omo-filter'))closePanels();});document.addEventListener('keydown',event=>{if(event.key==='Escape'){closePanels();closeOrder();}});
    const iso=date=>`${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
    function setDates(from,to,label){if(from&&to&&from>to){showError('The end date must be on or after the start date.');return;}state.date_from=from;state.date_to=to;state.page=1;el('omoDateFrom').value=from;el('omoDateTo').value=to;el('omoDateLabel').textContent=label||(from||to?`${from||'Any'} – ${to||'Any'}`:'All Dates');closePanels();load();}
    document.querySelectorAll('.omo-date-preset').forEach(btn=>btn.addEventListener('click',()=>{const today=app.dataset.today,date=new Date(today+'T00:00:00');if(btn.dataset.preset==='today')setDates(today,today,'Today');else if(btn.dataset.preset==='7days'){date.setDate(date.getDate()-6);setDates(iso(date),today,'Last 7 Days');}else if(btn.dataset.preset==='month'){date.setDate(1);setDates(iso(date),today,'This Month');}else setDates('','','All Dates');}));
    el('omoDateApply').addEventListener('click',()=>setDates(el('omoDateFrom').value,el('omoDateTo').value));el('omoDateClear').addEventListener('click',()=>setDates('','','All Dates'));
    el('omoPrevPage').addEventListener('click',()=>{if(state.page>1){state.page--;load();}});el('omoNextPage').addEventListener('click',()=>{if(state.page<pagination.last){state.page++;load();}});
    el('omoDateFrom').value=state.date_from;el('omoDateTo').value=state.date_to;if(state.date_from||state.date_to)el('omoDateLabel').textContent=`${state.date_from||'Any'} – ${state.date_to||'Any'}`;
    sync(initial.counts);if(initial.filters.order)openOrder(initial.filters.order);
    setInterval(async()=>{
        if(document.hidden||saving)return;
        await load();
        if(!currentOrder)return;
        const focused=document.activeElement;
        if(focused&&focused.closest('#omoDrawer')&&focused.matches('input,textarea,select'))return;
        try{
            const data=await api(app.dataset.orderBase+'/'+currentOrder);
            if(data.html!==drawerHtml){
                el('omoDrawer').innerHTML=data.html;
                drawerHtml=data.html;
                sync();
            }
        }catch(error){showError(error.message);}
    },3000);
})();
