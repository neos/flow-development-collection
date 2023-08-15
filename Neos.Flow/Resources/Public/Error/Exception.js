const backtrace = document.querySelector(".Flow-Debug-Exception-Backtrace-Code");
const localStorageItemIdentifier = 'Flow-Debug-Exception-Backtrace-Code-Open';

if (window.localStorage.getItem(localStorageItemIdentifier) === "true") {
    backtrace.open = true;
}

backtrace.addEventListener("click", (event) => {
    if (backtrace.open === true) {
        window.localStorage.removeItem(localStorageItemIdentifier);
    } else {
        window.localStorage.setItem(localStorageItemIdentifier, true);
    }
});
