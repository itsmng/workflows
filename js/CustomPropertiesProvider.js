import { is } from 'bpmn-js/lib/util/ModelUtil';
import { SelectEntry, isSelectEntryEdited } from '@bpmn-io/properties-panel';
import { useService } from 'bpmn-js-properties-panel';
import { html } from 'htm/preact';

export class CustomPropertiesProvider {
  constructor(propertiesPanel, translate) {
    const LOW_PRIORITY = 500;
    propertiesPanel.registerProvider(LOW_PRIORITY, this);
  }

  getGroups(element) {
    return function (groups) {
      if (is(element, 'bpmn:Task')) {
        groups.push(createCustomGroup(element));
      }
      return groups;
    };
  }
}

function createCustomGroup(element) {
  return {
    id: 'custom-actions',
    label: 'Custom Tasks',
    entries: [
      {
        id: 'action',
        component: CustomField,
        isEdited: isSelectEntryEdited,
        label: 'Action',
        getValue: (element) => {
          return element.businessObject.spell || '';
        },
        setValue: (element, value) => {
          element.businessObject.spell = value;
        }
      }
    ]
  };
}

function CustomField(props) {
  const { element, id } = props;

  const modeling = useService('modeling');
  const translate = useService('translate');

  const getValue = () => {
    return element.businessObject.spell || '';
  };

  const setValue = (value) => {
    return modeling.updateProperties(element, {
      spell: value
    });
  };

  const getOptions = () => {
    return [
      { value: 'oui', label: translate('Oui') },
      { value: 'non', label: translate('Non') }
    ];
  };

  return html`<${SelectEntry}
    id=${id}
    element=${element}
    label=${translate('Créer un ticket ?')}
    getValue=${getValue}
    setValue=${setValue}
    getOptions=${getOptions}
  />`;
}

export default CustomPropertiesProvider;
