// Include helper.
// If we directly include javascript files, then smarty will brake because core js files are not
// optimized to support smarty.


let includedJs = [
    '{link file="backend/_resources/js/AjaxService.js"}',
];

(function () {
    for (let file of includedJs) {
        let script = document.createElement('script');
        script.src = file;
        document.head.appendChild(script);
    }
})();