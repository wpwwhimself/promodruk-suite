import {
	ClassicEditor,
	AccessibilityHelp,
	Alignment,
	Autoformat,
	AutoImage,
	AutoLink,
	Autosave,
	BalloonToolbar,
	BlockQuote,
	Bold,
	CloudServices,
	Code,
	CodeBlock,
	Essentials,
	FindAndReplace,
	FontBackgroundColor,
	FontColor,
	FontFamily,
	FontSize,
	FullPage,
	GeneralHtmlSupport,
	Heading,
	Highlight,
	HorizontalLine,
	HtmlComment,
	HtmlEmbed,
	ImageBlock,
	ImageCaption,
	ImageInline,
	ImageInsertViaUrl,
	ImageResize,
	ImageStyle,
	ImageTextAlternative,
	ImageToolbar,
	ImageUpload,
	Indent,
	IndentBlock,
	Italic,
	Link,
	LinkImage,
	List,
	ListProperties,
	Markdown,
	MediaEmbed,
	PageBreak,
	Paragraph,
	PasteFromMarkdownExperimental,
	PasteFromOffice,
	RemoveFormat,
	SelectAll,
	ShowBlocks,
	SourceEditing,
	SpecialCharacters,
	SpecialCharactersArrows,
	SpecialCharactersCurrency,
	SpecialCharactersEssentials,
	SpecialCharactersLatin,
	SpecialCharactersMathematical,
	SpecialCharactersText,
	Strikethrough,
	Style,
	Subscript,
	Superscript,
	Table,
	TableCaption,
	TableCellProperties,
	TableColumnResize,
	TableProperties,
	TableToolbar,
	TextTransformation,
	TodoList,
	Underline,
	Undo
} from 'ckeditor5';

import translations from 'ckeditor5/translations/pl.js';

window.CKEditorInit = function (field_name = undefined) {
    const editorConfig = {
        toolbar: {
            items: [
                'undo',
                'redo',
                '|',
                'sourceEditing',
                'showBlocks',
                'findAndReplace',
                '|',
                'heading',
                'style',
                '|',
                'fontSize',
                'fontFamily',
                'fontColor',
                'fontBackgroundColor',
                '|',
                'bold',
                'italic',
                'underline',
                'strikethrough',
                'subscript',
                'superscript',
                'code',
                'removeFormat',
                '|',
                'specialCharacters',
                'horizontalLine',
                'pageBreak',
                'link',
                'insertImageViaUrl',
                'mediaEmbed',
                'insertTable',
                'highlight',
                'blockQuote',
                'codeBlock',
                'htmlEmbed',
                '|',
                'alignment',
                '|',
                'bulletedList',
                'numberedList',
                'todoList',
                'outdent',
                'indent'
            ],
            shouldNotGroupWhenFull: false
        },
        plugins: [
            AccessibilityHelp,
            Alignment,
            Autoformat,
            AutoImage,
            AutoLink,
            Autosave,
            BalloonToolbar,
            BlockQuote,
            Bold,
            CloudServices,
            Code,
            CodeBlock,
            Essentials,
            FindAndReplace,
            FontBackgroundColor,
            FontColor,
            FontFamily,
            FontSize,
            FullPage,
            GeneralHtmlSupport,
            Heading,
            Highlight,
            HorizontalLine,
            HtmlComment,
            HtmlEmbed,
            ImageBlock,
            ImageCaption,
            ImageInline,
            ImageInsertViaUrl,
            ImageResize,
            ImageStyle,
            ImageTextAlternative,
            ImageToolbar,
            ImageUpload,
            Indent,
            IndentBlock,
            Italic,
            Link,
            LinkImage,
            List,
            ListProperties,
            Markdown,
            MediaEmbed,
            PageBreak,
            Paragraph,
            PasteFromMarkdownExperimental,
            PasteFromOffice,
            RemoveFormat,
            SelectAll,
            ShowBlocks,
            SourceEditing,
            SpecialCharacters,
            SpecialCharactersArrows,
            SpecialCharactersCurrency,
            SpecialCharactersEssentials,
            SpecialCharactersLatin,
            SpecialCharactersMathematical,
            SpecialCharactersText,
            Strikethrough,
            Style,
            Subscript,
            Superscript,
            Table,
            TableCaption,
            TableCellProperties,
            TableColumnResize,
            TableProperties,
            TableToolbar,
            TextTransformation,
            TodoList,
            Underline,
            Undo
        ],
        balloonToolbar: ['bold', 'italic', '|', 'link', '|', 'bulletedList', 'numberedList'],
        fontFamily: {
            supportAllValues: true
        },
        fontSize: {
            options: [10, 12, 14, 'default', 18, 20, 22],
            supportAllValues: true
        },
        fontColor: {
            colors: [
                {
                    label: "biały",
                    color: "#ffffff"
                },
                {
                    label: "bordowy",
                    color: "#800000",
                },
                {
                    label: "brązowy",
                    color: "#964B00",
                },
                {
                    label: "cyjan",
                    color: "#00B7EB",
                },
                {
                    label: "czarny",
                    color: "#000000",
                },
                {
                    label: "czerwony",
                    color: "#FF0000",
                },
                {
                    label: "fioletowy",
                    color: "#B803FF",
                },
                {
                    label: "grafitowy",
                    color: "#36454F",
                },
                {
                    label: "granatowy",
                    color: "#000080",
                },
                {
                    label: "magenta",
                    color: "#FF00FF",
                },
                {
                    label: "niebieski",
                    color: "#0000FF",
                },
                {
                    label: "pomarańczowy",
                    color: "#FE7F00",
                },
                {
                    label: "różowy",
                    color: "#F19CBB",
                },
                {
                    label: "zieleń butelkowa",
                    color: "#326647",
                },
                {
                    label: "zieleń jaskrawa",
                    color: "#00FF00",
                },
                {
                    label: "zielony",
                    color: "#008000",
                },
                {
                    label: "żółty",
                    color: "#FFFF00",
                },
            ],
        },
        fontBackgroundColor: {
            colors: [
                {
                    label: "biały",
                    color: "#ffffff"
                },
                {
                    label: "bordowy",
                    color: "#800000",
                },
                {
                    label: "brązowy",
                    color: "#964B00",
                },
                {
                    label: "cyjan",
                    color: "#00B7EB",
                },
                {
                    label: "czarny",
                    color: "#000000",
                },
                {
                    label: "czerwony",
                    color: "#FF0000",
                },
                {
                    label: "fioletowy",
                    color: "#B803FF",
                },
                {
                    label: "grafitowy",
                    color: "#36454F",
                },
                {
                    label: "granatowy",
                    color: "#000080",
                },
                {
                    label: "magenta",
                    color: "#FF00FF",
                },
                {
                    label: "niebieski",
                    color: "#0000FF",
                },
                {
                    label: "pomarańczowy",
                    color: "#FE7F00",
                },
                {
                    label: "różowy",
                    color: "#F19CBB",
                },
                {
                    label: "zieleń butelkowa",
                    color: "#326647",
                },
                {
                    label: "zieleń jaskrawa",
                    color: "#00FF00",
                },
                {
                    label: "zielony",
                    color: "#008000",
                },
                {
                    label: "żółty",
                    color: "#FFFF00",
                },
            ],
        },
        heading: {
            options: [
                {
                    model: 'paragraph',
                    title: 'Paragraph',
                    class: 'ck-heading_paragraph'
                },
                {
                    model: 'heading1',
                    view: 'h1',
                    title: 'Heading 1',
                    class: 'ck-heading_heading1'
                },
                {
                    model: 'heading2',
                    view: 'h2',
                    title: 'Heading 2',
                    class: 'ck-heading_heading2'
                },
                {
                    model: 'heading3',
                    view: 'h3',
                    title: 'Heading 3',
                    class: 'ck-heading_heading3'
                },
                {
                    model: 'heading4',
                    view: 'h4',
                    title: 'Heading 4',
                    class: 'ck-heading_heading4'
                },
                {
                    model: 'heading5',
                    view: 'h5',
                    title: 'Heading 5',
                    class: 'ck-heading_heading5'
                },
                {
                    model: 'heading6',
                    view: 'h6',
                    title: 'Heading 6',
                    class: 'ck-heading_heading6'
                }
            ]
        },
        htmlSupport: {
            allow: [
                {
                    name: /^.*$/,
                    styles: true,
                    attributes: true,
                    classes: true
                }
            ]
        },
        image: {
            toolbar: [
                'toggleImageCaption',
                'imageTextAlternative',
                '|',
                'imageStyle:inline',
                'imageStyle:wrapText',
                'imageStyle:breakText',
                '|',
                'resizeImage'
            ]
        },
        language: 'pl',
        link: {
            addTargetToExternalLinks: true,
            defaultProtocol: 'https://',
            decorators: {
                toggleDownloadable: {
                    mode: 'manual',
                    label: 'Downloadable',
                    attributes: {
                        download: 'file'
                    }
                }
            }
        },
        list: {
            properties: {
                styles: true,
                startIndex: true,
                reversed: true
            }
        },
        menuBar: {
            isVisible: true
        },
        placeholder: 'Zacznij pisać...',
        style: {
            definitions: [
                {
                    name: 'Article category',
                    element: 'h3',
                    classes: ['category']
                },
                {
                    name: 'Title',
                    element: 'h2',
                    classes: ['document-title']
                },
                {
                    name: 'Subtitle',
                    element: 'h3',
                    classes: ['document-subtitle']
                },
                {
                    name: 'Info box',
                    element: 'p',
                    classes: ['info-box']
                },
                {
                    name: 'Side quote',
                    element: 'blockquote',
                    classes: ['side-quote']
                },
                {
                    name: 'Marker',
                    element: 'span',
                    classes: ['marker']
                },
                {
                    name: 'Spoiler',
                    element: 'span',
                    classes: ['spoiler']
                },
                {
                    name: 'Code (dark)',
                    element: 'pre',
                    classes: ['fancy-code', 'fancy-code-dark']
                },
                {
                    name: 'Code (bright)',
                    element: 'pre',
                    classes: ['fancy-code', 'fancy-code-bright']
                }
            ]
        },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
        },
        translations: [translations]
    };

    Array.from(document.querySelectorAll(".ckeditor" + (field_name ? `[name^="${field_name}"]` : '')))
        .filter(element => element.checkVisibility())
        .forEach(element =>
            ClassicEditor.create(element, editorConfig)
                .then(editor => {
                    editor.ui.focusTracker.on("change:isFocused", (ev, name, isFocused) => {
                        if (isFocused) return

                        element.innerHTML = editor.getData()
                        element.dispatchEvent(new Event("change"))
                    })
                })
        )
}

window.CKEditorInit()
