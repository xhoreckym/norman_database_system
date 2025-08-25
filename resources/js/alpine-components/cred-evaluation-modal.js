export default function credEvaluationModal() {
  return {
      showCredEvaluationModal: false,
      credEvaluationRecord: null,
      credEvaluationRecordId: null,
      credEvaluationData: {
          reliabilityScore: '',
          useOfStudy: '',
          comments: '',
          evaluationDate: ''
      },
      credEvaluationHistory: [],
      
      init() {
          // Initialize Alpine component
          console.log('CRED evaluation modal component initialized');
          
          // Set default evaluation date to today
          this.credEvaluationData.evaluationDate = new Date().toISOString().split('T')[0];
      },
      
      async openModalCredEvaluation(recordId) {
          try {
              console.log('Opening CRED evaluation modal for recordId:', recordId);
              this.credEvaluationRecordId = recordId;
              this.showCredEvaluationModal = true;
              this.credEvaluationRecord = null;
              this.credEvaluationHistory = [];
              
              // Reset form data
              this.credEvaluationData = {
                  reliabilityScore: '',
                  useOfStudy: '',
                  comments: '',
                  evaluationDate: new Date().toISOString().split('T')[0]
              };
              
              // Fetch record data for CRED evaluation
              // Replace this URL with your Laravel route for CRED evaluation data
              const url = `/ecotox/credevaluation/data/${recordId}`;
              const response = await fetch(url);
              
              if (!response.ok) {
                  throw new Error('Failed to fetch CRED evaluation data');
              }
              
              this.credEvaluationRecord = await response.json();
              console.log('CRED evaluation record data:', this.credEvaluationRecord);
              
              // Load previous evaluation history if available
              await this.loadEvaluationHistory(recordId);
              
          } catch (error) {
              console.error('Error opening CRED evaluation modal:', error);
              alert('Failed to load CRED evaluation data. Please try again.');
              this.closeModalCredEvaluation();
          }
      },
      
      closeModalCredEvaluation() {
          this.showCredEvaluationModal = false;
          this.credEvaluationRecord = null;
          this.credEvaluationRecordId = null;
          this.credEvaluationHistory = [];
          
          // Reset form data
          this.credEvaluationData = {
              reliabilityScore: '',
              useOfStudy: '',
              comments: '',
              evaluationDate: new Date().toISOString().split('T')[0]
          };
      },
      
      async loadEvaluationHistory(recordId) {
          try {
              // Fetch evaluation history for this record
              // Replace this URL with your Laravel route for evaluation history
              const url = `/ecotox/credevaluation/history/${recordId}`;
              const response = await fetch(url);
              
              if (response.ok) {
                  this.credEvaluationHistory = await response.json();
                  console.log('Evaluation history loaded:', this.credEvaluationHistory);
              }
          } catch (error) {
              console.error('Error loading evaluation history:', error);
              // Don't show error to user, just log it
          }
      },
      
      validateForm() {
          const errors = [];
          
          if (!this.credEvaluationData.reliabilityScore) {
              errors.push('Reliability score is required');
          }
          
          if (!this.credEvaluationData.useOfStudy) {
              errors.push('Use of study is required');
          }
          
          if (!this.credEvaluationData.evaluationDate) {
              errors.push('Evaluation date is required');
          }
          
          return errors;
      },
      
      async saveCredEvaluation() {
          try {
              // Validate form
              const errors = this.validateForm();
              if (errors.length > 0) {
                  alert('Please fix the following errors:\n' + errors.join('\n'));
                  return;
              }
              
              // Prepare data for saving
              const evaluationData = {
                  record_id: this.credEvaluationRecordId,
                  reliability_score: this.credEvaluationData.reliabilityScore,
                  use_of_study: this.credEvaluationData.useOfStudy,
                  comments: this.credEvaluationData.comments,
                  evaluation_date: this.credEvaluationData.evaluationDate,
                  evaluated_by: this.getCurrentUserId(), // You'll need to implement this
                  evaluated_at: new Date().toISOString()
              };
              
              console.log('Saving CRED evaluation:', evaluationData);
              
              // Make API call to save evaluation
              const response = await fetch('/ecotox/credevaluation/save', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                  },
                  body: JSON.stringify(evaluationData)
              });
              
              if (response.ok) {
                  const result = await response.json();
                  console.log('CRED evaluation saved successfully:', result);
                  
                  // Show success message
                  alert('CRED evaluation saved successfully!');
                  
                  // Reload evaluation history
                  await this.loadEvaluationHistory(this.credEvaluationRecordId);
                  
                  // Close modal
                  this.closeModalCredEvaluation();
                  
              } else {
                  const errorData = await response.json();
                  throw new Error(errorData.message || 'Failed to save evaluation');
              }
              
          } catch (error) {
              console.error('Save CRED evaluation error:', error);
              alert('Error saving CRED evaluation: ' + error.message);
          }
      },
      
      getCurrentUserId() {
          // This should return the current user's ID
          // You might need to implement this based on your authentication system
          // For now, returning a placeholder
          return 'current_user_id';
      },
      
      // Helper method to format reliability score for display
      formatReliabilityScore(score) {
          const scoreMap = {
              '1': '1 - Reliable without restrictions',
              '2': '2 - Reliable with restrictions',
              '3': '3 - Not reliable',
              '4': '4 - Not assignable'
          };
          return scoreMap[score] || score;
      },
      
      // Helper method to format use of study for display
      formatUseOfStudy(use) {
          const useMap = {
              'key': 'Key study',
              'supporting': 'Supporting study',
              'not_used': 'Not used'
          };
          return useMap[use] || use;
      }
  };
}

// Make it available globally for Alpine
window.credEvaluationModal = credEvaluationModal;
