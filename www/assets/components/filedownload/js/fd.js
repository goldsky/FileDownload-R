
function xhr() {
    var xmlHttp;
    try {
        xmlHttp = new XMLHttpRequest();
    } catch(e) {
        var XMLHttpVersions = new Array(
            "MSXML2.XMLHTTP.6.0",
            "MSXML2.XMLHTTP.5.0",
            "MSXML2.XMLHTTP.4.0",
            "MSXML2.XMLHTTP.3.0",
            "MSXML2.XMLHTTP",
            "Microsoft.XMLHTTP"
            );
        for (var i=0; i<XmlHttpVersions.length && !xmlHttp; i++) {
            try {
                xmlHttp = new ActiveXObject(XmlHttpVersions[i]);
            } catch (e) {}
        }
        if (!xmlHttp)
            alert("Error creating the xhr object.")
        else
            return xmlHttp;
    }
}

function fileDownload(page, link) {
    alert(link);
    countClick(link);
}

function dirOpen(page, link) {
    alert(link);
    countClick(link);
}

function countClick(link) {

}

function init() {
    alert("Dojo ready, version:" + dojo.version);
    // More initialization here
}
dojo.ready(init);