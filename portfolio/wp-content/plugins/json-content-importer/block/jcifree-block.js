( function( editor, components, i18n, element ) {
	const { __ } = wp.i18n;
	var el = wp.element.createElement;  //element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var RangeControl = wp.components.RangeControl;
	var ServerSideRender = wp.serverSideRender;
	var RadioControl = wp.components.RadioControl;
	var MenuItemsChoice  = wp.components.MenuItemsChoice;
	var ToggleControl = wp.components.ToggleControl;
	const { Button, Modal, Spinner } = wp.components;
	var apiFetch = wp.apiFetch;
	const { useState, useEffect } = wp.element;	

	function calcv (jsonData) {
		let jsonObj = JSON.parse(jsonData);
		let ret = "";
		if (jsonObj==null) {
			ret = __( 'Sorry, this JSON can\'t be handled by the free JCI-Plugin. You might try the PRO-JCI.', 'json-content-importer');
		} else {
		let apiURL = jsonObj.url;
		let basenode = jsonObj.basenode;
		let template = jsonObj.template;
		 ret = __('Template is inserted in the Block-Settings. Try it  by clicking "Try Template" there, please', 'json-content-importer') + '\n\n' + __('Template', 'json-content-importer') + ':\n' + template + '\n\n' + __('URL', 'json-content-importer') + ': ' + apiURL + '\n' + __('Basenode', 'json-content-importer') + ': ' + basenode + '\n';
		}
		return ret;
	}
	
	function JCIFreeCustomServerSideRender(props) {
		var attributes = props.attributes;
		var [renderedContent, setRenderedContent] = useState(null);

		wp.element.useEffect(function() {
			apiFetch({
				path: '/wp/jcifree/v1/post/block-renderer/',
				method: 'POST',
				data: { attributes: attributes }
			}).then(function(response) {
				//console.log(JSON.stringify(response, null, 4));
				if (response.renderedContent) {
					//console.log('response: '+ response.renderedContent);
					setRenderedContent(response.renderedContent);
				}
			})
			.catch(error => {
				console.error('Error fetching data:', error.message);
			});
		}, [attributes]);
		//}, [blockName, attributes]);
		return el('div', { dangerouslySetInnerHTML: { __html: (renderedContent) } });
	}	

	registerBlockType( 'jci/jcifree-block-script', { 
		title: __( 'JSON Content Importer FREE', 'json-content-importer'),
		description: __( 'Block with API-data', 'json-content-importer'), 
 		icon: 'welcome-add-page', 
		category: 'widgets', 
		attributes: { 
			apiURL: {
				type: 'string',
				default: '/json-content-importer/json/gutenbergblockexample1.json',
			},
			template: {
				type: 'string',
				default: 'hello: {hello}<br>{exampledate}: {exampledate:datetime,"d.m.Y, H:i:s",0}<br>{exampletimestamp}: {exampletimestamp:datetime,"d.m.Y, H:i:s",10}<br>{subloop:level1:-1}start: {level1.start}<br>{subloop-array:level1.level2:-1}level2: {level1.level2.key}<br>{subloop:level1.level2.data:-1}id: {level1.level2.data.id}, type: {level1.level2.data.type}<br>{/subloop:level1.level2.data}{/subloop-array:level1.level2}{/subloop:level1}',
			},
			basenode: {
				type: 'string',
				default: '',
			},
			urlgettimeout: {
				type: 'string',
				default: '5',
			},
			numberofdisplayeditems: {
				type: 'string',
				default: '',
			},
			oneofthesewordsmustbein: {
				type: 'string',
				default: '',
			},
			oneofthesewordsmustbeindepth: {
				type: 'string',
				default: '',
			},
			oneofthesewordsmustnotbein: {
				type: 'string',
				default: '',
			},
			oneofthesewordsmustnotbeindepth: {
				type: 'string',
				default: '',
			},
			toggleswitch: {
				type: 'boolean',
				default: false,
			},
			toggleswitchexample: {
				type: 'boolean',
				default: false,
			},
			toggleswitchjson: {
				type: 'boolean',
				default: false,
			},
		},

		edit: function( props ) {
			var attributes = props.attributes;
			var apiURL = props.attributes.apiURL;
			var template = props.attributes.template;
			var basenode = props.attributes.basenode;
			var toggleswitch = props.attributes.toggleswitch;
			var toggleswitchexample = props.attributes.toggleswitchexample;
			var toggleswitchjson = props.attributes.toggleswitchjson;
			var urlgettimeout = props.attributes.urlgettimeout;
			var numberofdisplayeditems = props.attributes.numberofdisplayeditems;
			var oneofthesewordsmustbein = props.attributes.oneofthesewordsmustbein;
			var oneofthesewordsmustbeindepth = props.attributes.oneofthesewordsmustbeindepth;
			var oneofthesewordsmustnotbein = props.attributes.oneofthesewordsmustnotbein;
			var oneofthesewordsmustnotbeindepth = props.attributes.oneofthesewordsmustnotbeindepth;
			var setAttributes = props.setAttributes;
			
			const [ isOpen, setOpen ] = useState( false );
			const [ data, setData ] = useState( null );
			const [ isLoading, setLoading ] = useState( false );
			const [renderTrigger, setRenderTrigger] = useState(0);
			const [ buttonText, setButtonText ] = useState('Try Template');
			
			const handleClickTryTemplate = () => {
				var templateNew = (props.attributes.template)
				if (templateNew.trim()==templateNew) {
					templateNew = templateNew + ' ';
				} else {
					templateNew = templateNew.trim();
				}
				props.setAttributes( { template: (templateNew) } );
			};

			const openModal = () => {
				setOpen( true );
				setLoading( true );
				//	setButtonText("You clicked me");

			const userToken = wpApiSettings.nonce;
			fetch('/wp-json/wp/jcifree/v1/get/crte/?url=' + encodeURIComponent(apiURL) + '&basenode='+ encodeURIComponent(basenode),
					{
						method: 'GET',
						headers: {
							'Authorization': `Bearer ${userToken}`,
							'Content-Type': 'application/json',
							'X-WP-Nonce': userToken
						}				
					}
				)
                .then(response => response.json())
                .then(jsonData => {
                    setData(jsonData);
                    setLoading(false);
					let jsonObj = JSON.parse(jsonData);
                    props.attributes.template = jsonObj.template;
                })
                .catch(error => {
                    console.error('Error requesting data:', error);
                    setLoading( false );
                });
			};
						
			const closeModal = () => setOpen( false );			
			
			return [
				el( InspectorControls, { key: 'inspector' },
					el( components.PanelBody, {
						title: __( 'Define API-URL and template to insert in block', 'json-content-importer' ),
						className: 'jci_free_block',
						initialOpen: true,
					},
						el( TextControl, {
							type: 'string',
							label: __( 'API-URL:', 'json-content-importer' ),
							help: __( 'if empty try: e1 for "example 1"', 'json-content-importer'),
							placeholder:  __( 'if empty try: e1', 'json-content-importer' ),		
							value: apiURL,
							onChange: function( newapiURL ) {
								props.setAttributes( { apiURL: newapiURL } );
							},
							key: 'apiURL'
						} ),
						el( ToggleControl, { 
							type: 'string',
							label: __( 'Show API answer', 'json-content-importer' ),
							//help : i18n.__( 'help' ),
							checked : !!toggleswitchjson,
							onChange: function( newtoggleswitchjson ) {
								props.setAttributes( { toggleswitchjson: newtoggleswitchjson } );
							},
							key: 'toggleswitchjson'
						} ), 
					el('div', { props },
						!isOpen && el(Button, { 
							isPrimary: true, 
							onClick: openModal, 
							key: 'openModalButton' }, 
							__('Create JCI-Template for JSON', 'json-content-importer' )),
						isOpen && el(Modal, { 
							title: ('Generate Template:'), 
							onRequestClose: closeModal, 
							key: 'modalWindow'
							},
						isLoading
							? el(Spinner, {
								key: 'spinner'}
							)
							: el('pre', {key: 'preData'}, calcv(data)),
						el(Button, { 
							isSecondary: true, 
							onClick: closeModal,
							key: 'closeModalButton'
							}, 'Close this window')
						)
					),
					el('div', { style: { height: '25px' }, key: 'divSpace1' }  ),
					el( ToggleControl, { // https://wordpress.org/gutenberg/handbook/components/toggle-control/
						type: 'string',
						label: __( 'Debugmode on / off', 'json-content-importer' ),
						checked : !!toggleswitch,
						onChange: function( newtoggleswitch ) {
							props.setAttributes( { toggleswitch: newtoggleswitch } );
						},
						key: 'toggleswitch'
					}  ), 
					el( ToggleControl, { // https://wordpress.org/gutenberg/handbook/components/toggle-control/
						type: 'string',
						label: __( 'Welcome on / off', 'json-content-importer' ),
						//help : i18n.__( 'help' ),
						checked : !!toggleswitchexample,
						onChange: function( newtoggleswitchexample ) {
							props.setAttributes( { toggleswitchexample: newtoggleswitchexample } );
						},
						key: 'toggleswitchexample'
					}  ), 
    				el( TextControl, {
						type: 'string',
						label: __( 'Basenode (JSON-node to start):', 'json-content-importer' ),
						help : __( 'if empty and initial example URL: level1', 'json-content-importer' ),
						placeholder:  __( 'if empty and above example URL: level1', 'json-content-importer' ),		
						value: basenode,
						onChange: function( newBasenode ) {
							props.setAttributes( { basenode: newBasenode } );
						},
						key: 'basenode'
					}  ),
					el(
						Button,
						{
							isPrimary: true,
							onClick: handleClickTryTemplate,
							key: 'tryTemplateButton'
						},
						buttonText
					),
					el('div', { style: { height: '25px' }, key: 'divSpace2' } ),
					el( TextareaControl, {
						type: 'string',
						label: __( 'Template to use for JSON:', 'json-content-importer' ),
						placeholder:  __( 'if emtpy: Click "Create JCI-Template for JSON" on the left side', 'json-content-importer' ),		
						help : __( 'Use {subloop}, {subloop-array}, {field} etc. just like in the shortcode', 'json-content-importer' ),
						rows: 10,
						value: template,
						onChange: function( newTemplate ) {
								props.setAttributes( { template: newTemplate } );
						},
						key: 'template'
					} ),
					el(
						Button,
						{
							isPrimary: true,
							onClick: function() {
								window.open('https://doc.json-content-importer.com', '_blank');
							},
							key: 'openManualButton'							
						},
						 __( 'Open JCI-Manual', 'json-content-importer' )
					),
				),
				el( components.PanelBody, {
					title: __( 'JCI Advanced', 'json-content-importer' ),
						className: 'jci_free_block',
						initialOpen: false,
					},
    				el( TextControl, {
						type: 'string',
						label: __( 'Number of seconds waiting for the API:', 'json-content-importer' ),
						placeholder: __( 'default: 5 seconds', 'json-content-importer' ),		
						//help : __( 'Number of seconds waiting for the API', 'json-content-importer' ),
						value: urlgettimeout,
						onChange: function( newurlgettimeout ) {
							props.setAttributes( { urlgettimeout: newurlgettimeout } );
						},
						key: 'urlgettimeout'
					} ),
    				el( TextControl, {
						type: 'string',
						label: __( 'Number of json-top-level-items to display:', 'json-content-importer' ),
						placeholder:  __( 'default: all', 'json-content-importer' ),		
						value: numberofdisplayeditems,
						onChange: function( newnumberofdisplayeditems ) {
							props.setAttributes( { numberofdisplayeditems: newnumberofdisplayeditems } );
						},
						key: 'numberofdisplayeditems'
					} ),
					el( TextControl, {
						type: 'string',
						label: __( 'One of these words must be displayed:', 'json-content-importer' ),
						placeholder:  __( 'default: empty', 'json-content-importer' ),		
						value: oneofthesewordsmustbein,
						onChange: function( newoneofthesewordsmustbein ) {
							props.setAttributes( { oneofthesewordsmustbein: newoneofthesewordsmustbein } );
						},
						key: 'oneofthesewordsmustbein'
					}  ),
    				el( TextControl, {
						type: 'string',
						label: __( 'JSON-depth of the above displayed Words:', 'json-content-importer' ),
						placeholder:  __( 'default: empty', 'json-content-importer' ),		
						value: oneofthesewordsmustbeindepth,
						onChange: function( newoneofthesewordsmustbeindepth ) {
							props.setAttributes( { oneofthesewordsmustbeindepth: newoneofthesewordsmustbeindepth } );
						},
						key: 'oneofthesewordsmustbeindepth'
					} ),
    				el( TextControl, {
						type: 'string',
						label: __( 'NONE of these words must be displayed:', 'json-content-importer' ),
						placeholder:  __( 'default: empty', 'json-content-importer' ),		
						value: oneofthesewordsmustnotbein,
						onChange: function( newoneofthesewordsmustnotbein ) {
							props.setAttributes( { oneofthesewordsmustnotbein: newoneofthesewordsmustnotbein } );
						},
						key: 'oneofthesewordsmustnotbein'
					}  ),
    				el( TextControl, {
						type: 'string',
						label: __( 'JSON-depth of the above NOT displayed Words:', 'json-content-importer' ),
						placeholder:  __( 'default: empty', 'json-content-importer' ),		
						value: oneofthesewordsmustnotbeindepth,
						onChange: function( newoneofthesewordsmustnotbeindepth ) {
							props.setAttributes( { oneofthesewordsmustnotbeindepth: newoneofthesewordsmustnotbeindepth } );
						},
						key: 'oneofthesewordsmustnotbeindepth'
					} ),
				),
			),
			el(JCIFreeCustomServerSideRender, {
				//blockName: 'jcifreeblock',
				attributes: attributes,
				key: 'jciFreecustomServerSideRender'
			})
			];
		},
		
		save: function() {
			return null;
		},
	} );

} )(
	window.wp.editor,
	window.wp.components,
	window.wp.i18n,
	window.wp.element,
);
