
jQuery(function ($) {
    /* 
    localized variables passed through params object:
    salesforce_nfpluginsettings.clearLogRestUrl
    salesforce_nfpluginsettings.clearLogButtonId  
    salesforce_nfpluginsettings.downloadLogRestUrl
    salesforce_nfpluginsettings.downloadLogButtonId
    
    */

 $("#"+salesforce_nfpluginsettings.clearLogButtonId).on("click", () => {

   $.post(
      salesforce_nfpluginsettings.clearLogRestUrl,
     { },
     function (json) {
       console.log(json);
     }
   );
 });

 $("#"+salesforce_nfpluginsettings.downloadLogButtonId).on("click", () => {
   $.post(
     salesforce_nfpluginsettings.downloadLogRestUrl,
     {},
     function (json) {
       let download = json.data;

       var blob = new Blob([download], { type: "json" });

       var a = document.createElement("a");
       a.download = "nf-salesforce-debug-log.json";
       a.href = URL.createObjectURL(blob);
       a.dataset.downloadurl = ["json", a.download, a.href].join(":");
       a.style.display = "none";
       document.body.appendChild(a);
       a.click();
       document.body.removeChild(a);
       setTimeout(function () {
         URL.revokeObjectURL(a.href);
       }, 15000);
     }
   );
 });

return;

});
