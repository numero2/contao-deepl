(()=>{

    class InputField {

        #widget;
        #field;
        #tinyInstance;

        constructor(widget, type) {

            this.#widget = widget;
            this.#field = widget.querySelector('input[type="text"], textarea');

            this.#determineFieldType();
        }

        #determineFieldType() {

            // tinyMCE
            if( window.tinyMCE ) {

                let instances = tinyMCE.get();

                [...instances].forEach(tiny=>{

                    if( tiny.targetElm === this.#field ) {

                        this.#tinyInstance = tiny;
                        return;
                    }
                });

                if( this.#tinyInstance ) {
                    return;
                }
            }

            // wizards
            let wizardFields = this.#widget.querySelectorAll('.tl_optionwizard input[type="text"], .tl_listwizard input[type="text"] , .tl_key_value_wizard input[type="text"]')

            if( wizardFields.length ) {
                this.#field = wizardFields;
            }
        }

        getInput() {
            return this.#field
        }

        getContent() {

            if( this.#tinyInstance ) {
                return this.#tinyInstance.getContent();
            }

            if( this.#field instanceof NodeList && this.#field.length ) {

                let rows = [];

                [...this.#field].forEach((i)=>{
                    rows.push(i.value);
                });

                return rows;
            }

            return this.#field.value;
        }

        setContent(content) {

            if( this.#tinyInstance ) {

                this.#tinyInstance.setContent(content);

            } else if( this.#field instanceof NodeList ) {

                [...this.#field].forEach((input,i)=>{

                    input.value = content[i];
                });

            } else {

                this.#field.value = content;

                // trigger change event for other scripts
                const change = new Event('change', {
                    bubbles: true,
                    cancelable: true
                });

                this.#field.dispatchEvent(change);
            }
        }
    }


    const translate = function(e) {

        e.preventDefault();

        let button = this;
        let field = new InputField(this.closest('.widget'));
        let input = field.getInput();

        // remove button if field is readonly or disabled
        if( input.readOnly || input.disabled ) {
            button.remove();
            return;
        }

        let content = field.getContent();

        if( !content.length ) {
            return;
        }

        button.dataset.loading = true;

        fetch(`${window.DeepL.base}/deepl/translate?lang=${window.DeepL.target}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ content: content })
        })
        .then(response => response.json())
        .then(data => {
            field.setContent(data.translation);
        })
        .catch(error => console.error('Error:', error))
        .finally(()=>{
            button.removeAttribute('data-loading');
        });

    };

    const initDeepLTranslateButtons = () => {

        const buttons = document.querySelectorAll('.widget button.deepl-translate');

        [...buttons].forEach((button)=>{
            button.addEventListener('click',translate);
        });

        if( buttons.length ) {

            document.addEventListener('keydown',(e)=> {

                if( e.altKey && e.keyCode === 84) {

                    e.preventDefault();
                    console.info('DeepL: Translating all Widgets');

                    [...buttons].forEach((button)=>{
                        button.click();
                    });
                }
            });
        }
    };

    if( typeof window.Turbo !== "undefined") {
        document.addEventListener('turbo:load', initDeepLTranslateButtons);
    } else {
        document.addEventListener('DOMContentLoaded', initDeepLTranslateButtons);
    }

})();
