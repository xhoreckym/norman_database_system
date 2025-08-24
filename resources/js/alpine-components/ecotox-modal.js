export default function ecotoxModal() {
  return {
      showModal: false,
      record: null,
      recordId: null,
      tableRows: [],
      
      init() {
          // Initialize Alpine component
          console.log('Ecotox modal component initialized');
      },
      
      getTableSections() {
          return [
              'Source',
              'Reference',
              'Categorisation',
              'Test substance',
              'Biotest',
              'Test Organism',
              'Dosing system',
              'Controls and Study design',
              'Test conditions',
              'Statistical design',
              'Biological effect',
              'Evaluation'
          ];
      },
      
      buildTableRows() {
          const rows = [];
          let rowId = 0;
          
          if (!this.record?.table_data) {
              this.tableRows = rows;
              return;
          }
          
          this.getTableSections().forEach(sectionName => {
              const sectionData = this.record.table_data[sectionName];
              
              if (sectionData && Object.keys(sectionData).length > 0) {
                  // Add section header
                  rows.push({
                      id: `header-${rowId++}`,
                      type: 'header',
                      title: sectionName + ' Information'
                  });
                  
                  // Add data rows
                  Object.entries(sectionData).forEach(([key, value], index) => {
                      rows.push({
                          id: `data-${rowId++}`,
                          type: 'data',
                          key: key,
                          sectionName: sectionName,
                          columnId: value?.column_id,
                          original: value?.data?.original || '',
                          harmonised: value?.data?.harmonised || '',
                          final: value?.data?.final || '',
                          isEditable: value?.is_editable || false,
                          inputType: value?.input_type || 'text',
                          dropdownOptions: value?.input_values && value.input_values.length > 0 ? value.input_values : ['Yes', 'No', 'Unknown', 'Not applicable'],
                          isOdd: index % 2 !== 0
                      });
                  });
              }
          });
          
          this.tableRows = rows;
      },
      
      async openModal(recordId) {
          try {
              console.log('Opening modal for recordId:', recordId);
              this.recordId = recordId;
              this.showModal = true;
              this.record = null;
              this.tableRows = [];
              
              // Replace this URL with your Laravel route
              const url = `/ecotox/show/${recordId}`;
              const response = await fetch(url);
              
              if (!response.ok) {
                  throw new Error('Failed to fetch record data');
              }
              
              this.record = await response.json();
              console.log('Ecotox record data:', this.record);
              
              // Build table rows after data is loaded
              this.buildTableRows();
              
              // Attach events after DOM updates
              this.$nextTick(() => {
                  this.attachInputEvents();
              });
              
          } catch (error) {
              console.error('Error opening modal:', error);
              alert('Failed to load record data. Please try again.');
              this.closeModal();
          }
      },
      
      closeModal() {
          this.showModal = false;
          this.record = null;
          this.recordId = null;
          this.tableRows = [];
      },
      
      attachInputEvents() {
          // Events are now handled by Alpine's x-model
          console.log('Input events ready');
      },
      
      updateField(rowId, newValue) {
          const row = this.tableRows.find(r => r.id === rowId);
          if (row && row.type === 'data') {
              row.final = newValue;
              console.log(`Updated ${row.sectionName}.${row.key} to ${newValue}`);
              
              // Update the original record data as well
              if (this.record?.table_data?.[row.sectionName]?.[row.key]) {
                  if (!this.record.table_data[row.sectionName][row.key].data) {
                      this.record.table_data[row.sectionName][row.key].data = {};
                  }
                  this.record.table_data[row.sectionName][row.key].data.final = newValue;
              }
          }
      },
      
      async saveChanges() {
          // Collect all changes and send to server
          const changes = {};
          this.tableRows.forEach(row => {
              if (row.type === 'data' && row.isEditable) {
                  if (!changes[row.sectionName]) {
                      changes[row.sectionName] = {};
                  }
                  changes[row.sectionName][row.key] = row.final;
              }
          });
          
          console.log('Saving changes:', changes);
          
          // Make API call to save
          try {
              const response = await fetch(`/ecotox/update/${this.recordId}`, {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                  },
                  body: JSON.stringify(changes)
              });
              
              if (response.ok) {
                  alert('Changes saved successfully');
              } else {
                  alert('Failed to save changes');
              }
          } catch (error) {
              console.error('Save error:', error);
              alert('Error saving changes');
          }
      }
  };
}

// Make it available globally for Alpine
window.ecotoxModal = ecotoxModal;