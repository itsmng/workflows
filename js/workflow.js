import { BPMNClient } from 'bpmn-client';

function getBpmnClient() {
  const API_KEY = '12345';
  const HOST = 'localhost';
  const PORT = '3000';

  const server = new BPMNClient(HOST, PORT, API_KEY);
  return server;
}

document.addEventListener("DOMContentLoaded", function () {
  const BpmnPropertiesPanel = window.BpmnJSPropertiesPanel;
  const bpmnModeler = new BpmnJS({
    container: '#canvas',
    propertiesPanel: {
      parent: '#js-properties-panel'
    },
    additionalModules: [
      BpmnPropertiesPanel.BpmnPropertiesPanelModule,
      BpmnPropertiesPanel.BpmnPropertiesProviderModule,
    ],
  });
  const modelerWrapper = document.getElementById('bpmn-modeler');
  const workflowName = modelerWrapper.getAttribute('data-model');
  const server = getBpmnClient();
  server.definitions.load(workflowName).then((res) => {
    if (res.error) {
      console.error(res.error);
    } else {
      bpmnModeler.importXML(res);
      bpmnModeler.get('canvas').zoom('fit-viewport');
    }
  });
});

$(function () {
  $('#save-diagram').click(function () {
    const modelerWrapper = document.getElementById('bpmn-modeler');
    const workflowName = modelerWrapper.getAttribute('data-model');
    const server = getBpmnClient();
    server.definitions.save(workflowName).then((res) => {
      if (res.error) {
        console.error(res.error);
      } else {
        console.log(res);
      }
    });
  });
});
