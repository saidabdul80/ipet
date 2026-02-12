import{u as B}from"./appSettings-DlaUMDbI.js";import{_ as I}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{c as v,g as O,f as n,w as c,h as m,s as P,W as E,i as e,n as b,G as L,p as y,v as j,e as o,j as p,t as s,J as k,K as C,H as q,a9 as F,T as M,aa as W}from"./app-CJq9zEpr.js";const $={id:"receipt-content",class:"thermal-receipt"},G={class:"receipt-header"},U={class:"company-name"},Y={key:0,class:"info-line"},H={key:1,class:"info-line"},K={key:2,class:"info-line"},J={class:"receipt-info"},Q={class:"info-row"},X={class:"value bold"},Z={class:"info-row"},tt={class:"value"},et={class:"info-row"},st={class:"value"},at={class:"info-row"},nt={class:"value"},it={class:"items-section"},ot={class:"item-row-content"},lt={class:"item-name"},dt={class:"item-qty"},rt={class:"item-price"},mt={class:"item-total bold"},pt={class:"totals-section"},ct={class:"total-row"},ut={class:"value"},gt={key:0,class:"total-row discount"},ft={class:"value"},xt={key:1,class:"total-row"},vt={class:"value"},bt={class:"total-row grand-total"},yt={class:"value"},ht={class:"payment-section"},wt={key:0,class:"split-payment"},_t={class:"payment-row"},kt={class:"label"},Ct={class:"value bold"},Vt={key:0,class:"payment-ref"},St={class:"total-row payment-total"},zt={class:"value bold"},Tt={key:1,class:"total-row change"},Nt={class:"value bold"},Dt={key:2,class:"total-row outstanding"},Rt={class:"value bold"},At={class:"receipt-footer"},Bt={key:0,class:"info-line"},It={class:"info-line receipt-date"},Ot={__name:"SaleReceipt",props:{modelValue:Boolean,sale:{type:Object,required:!0}},emits:["update:modelValue"],setup(a,{emit:V}){const u=a,S=V,z=B(),d=v(()=>z.settings),f=v({get:()=>u.modelValue,set:i=>S("update:modelValue",i)}),T=v(()=>u.sale.payments&&u.sale.payments.length>1),h=()=>{f.value=!1},N=()=>{const i=document.getElementById("receipt-content"),t=window.open("","_blank");t.document.write(`
    <html>
      <head>
        <title>Receipt - ${u.sale.invoice_number}</title>
        <style>
          * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
          }
          body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.2;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm 2mm;
            background: white;
          }
          .thermal-receipt {
            width: 100%;
          }
          .receipt-header {
            text-align: center;
            margin-bottom: 8px;
          }
          
          .company-name {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
          }
          .info-line {
            font-size: 9px;
            margin-bottom: 1px;
          }
          .divider-double {
            text-align: center;
            margin: 6px 0;
            font-size: 10px;
            letter-spacing: -1px;
          }
          .divider-thin {
            text-align: center;
            margin: 4px 0;
            font-size: 10px;
            letter-spacing: -0.5px;
          }
          .receipt-info, .payment-section {
            margin-bottom: 6px;
          }
          .info-row, .total-row, .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 11px;
          }
          .label {
            text-align: left;
          }
          .value {
            text-align: right;
          }
          .bold {
            font-weight: bold;
          }
          /* Fixed Items Section */
          .items-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
          }
          .header-item { 
            flex: 1; 
            text-align: left;
            min-width: 120px;
          }
          .header-qty { 
            width: 40px; 
            text-align: center;
          }
          .header-price { 
            width: 60px; 
            text-align: right;
          }
          .header-total { 
            width: 60px; 
            text-align: right;
          }
          .items-section {
            margin-bottom: 6px;
          }
          .item-row {
            margin-bottom: 4px;
          }
          .item-row-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 11px;
          }
          .item-name {
            flex: 1;
            text-align: left;
            min-width: 120px;
            word-wrap: break-word;
          }
          .item-qty {
            width: 40px;
            text-align: center;
          }
          .item-price {
            width: 60px;
            text-align: right;
          }
          .item-total {
            width: 60px;
            text-align: right;
            font-weight: bold;
          }
          .totals-section {
            margin-bottom: 6px;
          }
          .grand-total {
            font-size: 13px;
            font-weight: bold;
            margin-top: 3px;
            padding-top: 3px;
            border-top: 1px dotted #000;
          }
          .grand-total .label,
          .grand-total .value {
            font-size: 13px;
          }
          .discount .value {
            color: #d32f2f;
          }
          .section-title {
            font-weight: bold;
            margin-bottom: 4px;
            text-align: center;
            font-size: 10px;
            letter-spacing: 0.5px;
          }
          .split-payment {
            margin-bottom: 6px;
            background: #f5f5f5;
            padding: 6px;
            border: 1px dashed #999;
          }
          .payment-item {
            margin-bottom: 3px;
          }
          .payment-ref {
            font-size: 9px;
            margin-left: 10px;
            color: #666;
            font-style: italic;
          }
          .payment-total {
            font-weight: bold;
          }
          .change .value {
            color: #2e7d32;
            font-weight: bold;
          }
          .outstanding {
            font-weight: bold;
          }
          .outstanding .value {
            color: #f57c00;
          }
          .receipt-footer {
            text-align: center;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #999;
          }
          .thank-you {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 1px;
          }
          .receipt-date {
            font-size: 8px;
            color: #666;
            margin-top: 4px;
          }
          @media print {
            body {
              padding: 0;
              width: 80mm;
            }
            @page {
              size: 80mm auto;
              margin: 0;
            }
          }
        </style>
      </head>
      <body>
        ${i.innerHTML}
      </body>
    </html>
  `),t.document.close(),t.focus(),setTimeout(()=>{t.print(),t.close()},250)},r=i=>!i&&i!==0?`${d.value.currency_symbol||"₦"}0.00`:`${d.value.currency_symbol||"₦"}${parseFloat(i).toLocaleString("en-NG",{minimumFractionDigits:2,maximumFractionDigits:2})}`,D=i=>i?new Date(i).toLocaleString("en-NG",{year:"numeric",month:"short",day:"numeric",hour:"2-digit",minute:"2-digit"}):"",R=()=>new Date().toLocaleString("en-NG",{year:"numeric",month:"short",day:"numeric",hour:"2-digit",minute:"2-digit",second:"2-digit"}),A=i=>({cash:"Cash",card:"Card",bank_transfer:"Bank Transfer",wallet:"Wallet",mixed:"Mixed Payment"})[i]||i;return(i,t)=>(n(),O(W,{modelValue:f.value,"onUpdate:modelValue":t[0]||(t[0]=g=>f.value=g),"max-width":"400px",persistent:""},{default:c(()=>[m(P,null,{default:c(()=>[m(E,{class:"d-flex justify-space-between align-center bg-grey-lighten-4"},{default:c(()=>[t[2]||(t[2]=e("span",{class:"text-body-1"},"Receipt Preview",-1)),m(b,{icon:"",variant:"text",size:"small",onClick:h},{default:c(()=>[m(L,null,{default:c(()=>[...t[1]||(t[1]=[y("mdi-close",-1)])]),_:1})]),_:1})]),_:1}),m(j,{class:"pa-0"},{default:c(()=>{var g,w;return[e("div",$,[e("div",G,[e("div",U,s(d.value.app_name),1),d.value.company_address?(n(),o("div",Y,s(d.value.company_address),1)):p("",!0),d.value.company_phone?(n(),o("div",H,"Tel: "+s(d.value.company_phone),1)):p("",!0),d.value.company_email?(n(),o("div",K,s(d.value.company_email),1)):p("",!0)]),t[20]||(t[20]=e("div",{class:"divider-double"},null,-1)),e("div",J,[e("div",Q,[t[3]||(t[3]=e("span",{class:"label"},"Invoice #:",-1)),e("span",X,s(a.sale.invoice_number),1)]),e("div",Z,[t[4]||(t[4]=e("span",{class:"label"},"Date:",-1)),e("span",tt,s(D(a.sale.sale_date)),1)]),e("div",et,[t[5]||(t[5]=e("span",{class:"label"},"Cashier:",-1)),e("span",st,s(((g=a.sale.cashier)==null?void 0:g.name)||"N/A"),1)]),e("div",at,[t[6]||(t[6]=e("span",{class:"label"},"Customer:",-1)),e("span",nt,s(((w=a.sale.customer)==null?void 0:w.name)||"Walk-in"),1)])]),t[21]||(t[21]=e("div",{class:"divider-double"},null,-1)),t[22]||(t[22]=e("div",{class:"items-header"},[e("span",{class:"header-item"},"ITEM"),e("span",{class:"header-qty"},"QTY"),e("span",{class:"header-price"},"PRICE"),e("span",{class:"header-total"},"TOTAL")],-1)),t[23]||(t[23]=e("div",{class:"divider-thin"},"----------------------------------------------------",-1)),e("div",it,[(n(!0),o(k,null,C(a.sale.items,l=>{var x,_;return n(),o("div",{key:l.id,class:"item-row"},[e("div",ot,[e("span",lt,s(((x=l.product)==null?void 0:x.name)||"N/A"),1),e("span",dt,s(l.quantity)+" "+s(((_=l.unit)==null?void 0:_.short_name)||""),1),e("span",rt,s(r(l.unit_price)),1),e("span",mt,s(r(l.line_total)),1)])])}),128))]),t[24]||(t[24]=e("div",{class:"divider-double"},null,-1)),e("div",pt,[e("div",ct,[t[7]||(t[7]=e("span",{class:"label"},"Subtotal:",-1)),e("span",ut,s(r(a.sale.subtotal)),1)]),a.sale.discount_amount>0?(n(),o("div",gt,[t[8]||(t[8]=e("span",{class:"label"},"Discount:",-1)),e("span",ft,"-"+s(r(a.sale.discount_amount)),1)])):p("",!0),a.sale.tax_amount>0?(n(),o("div",xt,[t[9]||(t[9]=e("span",{class:"label"},"Tax:",-1)),e("span",vt,s(r(a.sale.tax_amount)),1)])):p("",!0),t[11]||(t[11]=e("div",{class:"divider-thin"},"----------------------------------------------------",-1)),e("div",bt,[t[10]||(t[10]=e("span",{class:"label"},"TOTAL:",-1)),e("span",yt,s(r(a.sale.total_amount)),1)])]),t[25]||(t[25]=e("div",{class:"divider-double"},null,-1)),e("div",ht,[T.value?(n(),o("div",wt,[t[12]||(t[12]=e("div",{class:"section-title"},"════ PAYMENT BREAKDOWN ════",-1)),(n(!0),o(k,null,C(a.sale.payments,(l,x)=>(n(),o("div",{key:l.id,class:"payment-item"},[e("div",_t,[e("span",kt,s(A(l.payment_method))+":",1),e("span",Ct,s(r(l.amount)),1)]),l.reference?(n(),o("div",Vt," Ref: "+s(l.reference),1)):p("",!0)]))),128)),t[13]||(t[13]=e("div",{class:"divider-thin"},"--------------------------------",-1))])):p("",!0),e("div",St,[t[14]||(t[14]=e("span",{class:"label"},"Amount Paid:",-1)),e("span",zt,s(r(a.sale.amount_paid)),1)]),a.sale.change_amount>0?(n(),o("div",Tt,[t[15]||(t[15]=e("span",{class:"label"},"Change:",-1)),e("span",Nt,s(r(a.sale.change_amount)),1)])):p("",!0),a.sale.outstanding_amount>0?(n(),o("div",Dt,[t[16]||(t[16]=e("span",{class:"label"},"Outstanding:",-1)),e("span",Rt,s(r(a.sale.outstanding_amount)),1)])):p("",!0)]),t[26]||(t[26]=e("div",{class:"divider-double"},null,-1)),e("div",At,[t[17]||(t[17]=e("div",{class:"thank-you"},"THANK YOU FOR YOUR BUSINESS!",-1)),t[18]||(t[18]=e("div",{class:"info-line"},"Items are non-returnable once opened",-1)),t[19]||(t[19]=e("div",{class:"info-line"},"Come again!",-1)),d.value.company_website?(n(),o("div",Bt,s(d.value.company_website),1)):p("",!0),e("div",It,s(R()),1)])])]}),_:1}),m(q),m(F,{class:"pa-4"},{default:c(()=>[m(M),m(b,{color:"grey",variant:"text",onClick:h},{default:c(()=>[...t[27]||(t[27]=[y("Close",-1)])]),_:1}),m(b,{color:"primary",variant:"flat",onClick:N,"prepend-icon":"mdi-printer"},{default:c(()=>[...t[28]||(t[28]=[y(" Print Receipt ",-1)])]),_:1})]),_:1})]),_:1})]),_:1},8,["modelValue"]))}},jt=I(Ot,[["__scopeId","data-v-6337975f"]]);export{jt as S};
