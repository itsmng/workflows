import { BPMNClient } from 'bpmn-client';
import CustomDescriptor from './Custom.json';
import CustomPropertiesProvider from './CustomPropertiesProvider.js';
import { BpmnPropertiesPanelModule } from 'bpmn-js-properties-panel';
import { BpmnPropertiesProviderModule } from 'bpmn-js-properties-panel';



function getBpmnClient() {
  const server = new BPMNClient(host, port, key);
  return server;
}

document.addEventListener('DOMContentLoaded', function () {


  const { BpmnJSPropertiesPanel } = window;
  
  const viewer = new BpmnJS({
    container: '#canvas',
    propertiesPanel: {
      parent: '#js-properties-panel'
    },
    additionalModules: [
      BpmnPropertiesPanelModule,
      BpmnPropertiesProviderModule,
      {
        __init__: ['customPropertiesProvider'],
        customPropertiesProvider: ['type', CustomPropertiesProvider]
      }
    ],
    moddleExtensions: {
      custom: CustomDescriptor
    }
  });

  const modelerWrapper = document.getElementById('bpmn-modeler');
  const workflowName = modelerWrapper.getAttribute('data-model');
  const server = getBpmnClient();

  server.definitions.load(workflowName).then((res) => {
    let xml = res;
    if (!xml) {
      xml = `
        <?xml version="1.0" encoding="UTF-8"?>
        <bpmn2:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:bpmn2="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" id="sample-diagram" targetNamespace="http://bpmn.io/schema/bpmn" xsi:schemaLocation="http://www.omg.org/spec/BPMN/20100524/MODEL BPMN20.xsd">
          <bpmn2:process id="Process_1" isExecutable="false">
            <bpmn2:startEvent id="StartEvent_1" />
          </bpmn2:process>
          <bpmndi:BPMNDiagram id="BPMNDiagram_1">
            <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
              <bpmndi:BPMNShape id="_BPMNShape_StartEvent_2" bpmnElement="StartEvent_1">
                <dc:Bounds x="412" y="240" width="36" height="36" />
              </bpmndi:BPMNShape>
            </bpmndi:BPMNPlane>
          </bpmndi:BPMNDiagram>
        </bpmn2:definitions>
      `;
    }

    viewer.importXML(xml);
    viewer.get('canvas').zoom('fit-viewport');
  });

  $('#save-diagram').click(function () {
    const modelerWrapper = document.getElementById('bpmn-modeler');
    const workflowName = modelerWrapper.getAttribute('data-model');
    const server = getBpmnClient();

    viewer.saveXML({ format: true }).then((res) => {
      const source = res.xml;
      
      server.definitions.save(workflowName, source).then((res) => {
        if (res.error) {
          console.error(res.error);
        } else {
          console.log('Diagramme sauvegardé avec succès :', res);
        }
      });
    });
  });
});
