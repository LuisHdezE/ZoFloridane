<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
    </main>

<script>
(function(){
    /* ── MÓDULO: Select all ── */
    try {
        var ca=document.getElementById('zfl-check-all');
        if(ca){ca.addEventListener('change',function(){document.querySelectorAll('.zfl-row-check').forEach(function(c){c.checked=ca.checked;});});}
    } catch(e) { console.error('ZFL select-all:', e); }

    /* ── MÓDULO: Selector de localidad global ── */
    try {
        var locSwitch=document.getElementById('zfl-loc-switch');
        if(locSwitch){
            locSwitch.addEventListener('change',function(){
                document.cookie='zfl_panel_loc='+encodeURIComponent(locSwitch.value)+';path=/;max-age=31536000';
                window.location.reload();
            });
        }
    } catch(e) { console.error('ZFL loc-switch:', e); }

    /* ── MÓDULO: Lightbox ── */
    try {
        var lb=document.getElementById('zfl-lightbox');
        if(lb){
            var lbI=lb.querySelector('.zfl-lightbox-img'),lbC=lb.querySelector('.zfl-lightbox-caption');
            function lbOp(s,n){if(!s)return;lbI.src=s;lbC.textContent=n||'';lb.hidden=false;document.body.classList.add('zfl-no-scroll');setTimeout(function(){lb.classList.add('zfl-lb-open');},10);}
            function lbCl(){lb.classList.remove('zfl-lb-open');document.body.classList.remove('zfl-no-scroll');setTimeout(function(){lb.hidden=true;lbI.src='';},250);}
            document.addEventListener('click',function(e){var b=e.target.closest('.zfl-thumb-button, [data-zfl-lightbox]');if(b){e.preventDefault();e.stopPropagation();lbOp(b.getAttribute('data-large'),b.getAttribute('data-name')||'');return;}});
            var lbX=lb.querySelector('.zfl-lightbox-close');
            if(lbX)lbX.addEventListener('click',lbCl);
            lb.addEventListener('click',function(e){if(e.target===lb)lbCl();});
            document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!lb.hidden)lbCl();});
        }
    } catch(e) { console.error('ZFL lightbox:', e); }

    /* ── MÓDULO: Modal de edición ── */
    try {
        var ov=document.getElementById('zfl-modal-overlay'),md=document.getElementById('zfl-modal');
        if(ov&&md){
            var mF=md.querySelector('.zfl-modal-form'),mT=md.querySelector('.zfl-modal-header h2');
            var mIP=md.querySelector('.zfl-modal-img-preview'),mPH=md.querySelector('.zfl-modal-img-placeholder');
            var mIN=md.querySelector('.zfl-modal-img-name'),mFI=md.querySelector('.zfl-modal-img-upload input[type="file"]');
            var mRC=md.querySelector('.zfl-modal-remove-img');

            function mOpen(d){
                if(!mF||!mT)return;
                mT.textContent='Editar: '+(d.name||'');
                var f=function(s){return mF.querySelector(s);};
                if(f('input[name="product_id"]'))f('input[name="product_id"]').value=d.id||'';
                if(f('input[name="name"]'))f('input[name="name"]').value=d.name||'';
                if(f('input[name="sale_price"]'))f('input[name="sale_price"]').value=d.sale||'';
                if(f('input[name="cost_price"]'))f('input[name="cost_price"]').value=(d.costPrice&&parseFloat(d.costPrice)>0)?d.costPrice:'';
                if(f('input[name="cost_rate"]'))f('input[name="cost_rate"]').value=(d.costRate&&parseFloat(d.costRate)>0)?d.costRate:'';
                if(f('input[name="stock_quantity"]'))f('input[name="stock_quantity"]').value=(d.stock===null||d.stock==='')?'':d.stock;
                if(f('select[name="status"]'))f('select[name="status"]').value=d.status||'publish';
                var fLoc=f('select[name="localidad"]');
                if(fLoc)fLoc.value=d.loc||'0';
                var cs=f('select[name="categories[]"]');
                if(cs){var cats=d.cats||[];Array.from(cs.options).forEach(function(o){o.selected=cats.indexOf(parseInt(o.value,10))!==-1;});}
                if(mRC){mRC.checked=false;mRC.value='0';}
                if(mFI)mFI.value='';
                if(d.thumb&&d.thumb!==''){mIP.src=d.thumb;mIP.style.display='block';mPH.style.display='none';mIN.textContent=d.name||'';}
                else{mIP.style.display='none';mPH.style.display='flex';mIN.textContent='';}
                ov.hidden=false;md.hidden=false;
                setTimeout(function(){ov.classList.add('zfl-modal-visible');md.classList.add('zfl-modal-visible');},10);
                document.body.classList.add('zfl-no-scroll');
            }

            function mCl(){ov.classList.remove('zfl-modal-visible');md.classList.remove('zfl-modal-visible');document.body.classList.remove('zfl-no-scroll');setTimeout(function(){ov.hidden=true;md.hidden=true;},250);}

            document.addEventListener('click',function(e){
                var b=e.target.closest('.zfl-edit-trigger');
                if(!b)return;
                e.preventDefault();
                var r=b.closest('tr')||b.closest('.zfl-mcard');if(!r)return;
                var cr=r.getAttribute('data-cats')||'';
                var cats=cr!==''?cr.split(',').map(Number).filter(Boolean):[];
                mOpen({id:r.getAttribute('data-product-id'),name:r.getAttribute('data-name'),price:r.getAttribute('data-price'),sale:r.getAttribute('data-sale'),costPrice:r.getAttribute('data-cost-price'),costRate:r.getAttribute('data-cost-rate'),stock:r.getAttribute('data-stock'),status:r.getAttribute('data-status'),thumb:r.getAttribute('data-thumb'),loc:r.getAttribute('data-loc'),cats:cats});
            });

            ov.addEventListener('click',mCl);
            var cx=md.querySelector('.zfl-modal-close'),cc=md.querySelector('.zfl-modal-cancel');
            if(cx)cx.addEventListener('click',mCl);
            if(cc)cc.addEventListener('click',mCl);
            document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!md.hidden)mCl();});

            if(mFI){mFI.addEventListener('change',function(){if(mFI.files&&mFI.files[0]){var r=new FileReader();r.onload=function(e){mIP.src=e.target.result;mIP.style.display='block';mPH.style.display='none';mIN.textContent=mFI.files[0].name;};r.readAsDataURL(mFI.files[0]);}});}

            var mRB=md.querySelector('.zfl-modal-img-remove');
            if(mRB){mRB.addEventListener('click',function(){mIP.style.display='none';mPH.style.display='flex';if(mRC){mRC.checked=true;mRC.value='1';}if(mFI)mFI.value='';mIN.textContent='';});}
        }
    } catch(e) { console.error('ZFL modal:', e); }

    /* ── MÓDULO: Header del panel (ocultar al bajar, mostrar al subir) ── */
    try {
        var topbar = document.querySelector('.zfl-topbar');
        if (topbar) {
            var lastTopY = window.scrollY || 0;
            window.addEventListener('scroll', function () {
                var y = window.scrollY || 0;
                if (y > lastTopY && y > 80) {
                    topbar.classList.add('zfl-topbar-hidden');
                } else {
                    topbar.classList.remove('zfl-topbar-hidden');
                }
                lastTopY = y;
            }, { passive: true });
        }
    } catch (e) { console.error('ZFL topbar scroll:', e); }

    /* ── MÓDULO: Preview de imágenes seleccionadas ── */
    try {
        document.addEventListener('change', function (e) {
            var input = e.target;
            if (!input || !input.matches || !input.matches('input[type="file"]')) return;
            var accept = (input.getAttribute('accept') || '').toLowerCase();
            if (accept.indexOf('image') === -1) return;
            var prev = input.parentElement.querySelector('.zfl-file-preview');
            if (!prev) {
                prev = document.createElement('img');
                prev.className = 'zfl-file-preview';
                prev.alt = '';
                input.insertAdjacentElement('afterend', prev);
            }
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (ev) { prev.src = ev.target.result; prev.hidden = false; };
                reader.readAsDataURL(input.files[0]);
            } else {
                prev.hidden = true;
            }
        });
    } catch (e) { console.error('ZFL file preview:', e); }

})();
</script>
</script>

</body>
</html>
