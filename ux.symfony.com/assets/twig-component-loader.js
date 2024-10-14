export default function (app) {
    const elements = document.querySelectorAll('[data-ux-component-controller-files]');

    elements.forEach((element) => {
        let file = element.getAttribute('data-ux-component-controller-files');
        let id = element.getAttribute('data-ux-component-id');

        import(file).then((result) => {
           app.register(id, result.default) ;
        });
    });
}