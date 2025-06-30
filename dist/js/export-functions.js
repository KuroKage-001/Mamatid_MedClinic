// Enhanced Export Functions for Dashboard Charts
(function() {
  'use strict';

  // Get current chart type for filename
  function getCurrentChartType() {
    return document.getElementById('chartType').value || 'weekly';
  }

  // Get formatted date for filename
  function getFormattedDate() {
    return new Date().toISOString().split('T')[0];
  }

  // Show success message
  function showSuccessMessage(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #28a745;
      color: white;
      padding: 12px 20px;
      border-radius: 5px;
      z-index: 9999;
      font-size: 14px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      animation: slideIn 0.3s ease-out;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
      if (document.body.contains(toast)) {
        toast.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
          if (document.body.contains(toast)) {
            document.body.removeChild(toast);
          }
        }, 300);
      }
    }, 3000);
  }

  // Show error message
  function showErrorMessage(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #dc3545;
      color: white;
      padding: 12px 20px;
      border-radius: 5px;
      z-index: 9999;
      font-size: 14px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      animation: slideIn 0.3s ease-out;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
      if (document.body.contains(toast)) {
        toast.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
          if (document.body.contains(toast)) {
            document.body.removeChild(toast);
          }
        }, 300);
      }
    }, 3000);
  }

  // Add CSS animations for toast notifications
  function addToastStyles() {
    if (!document.getElementById('toast-styles')) {
      const style = document.createElement('style');
      style.id = 'toast-styles';
      style.textContent = `
        @keyframes slideIn {
          from { transform: translateX(100%); opacity: 0; }
          to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
          from { transform: translateX(0); opacity: 1; }
          to { transform: translateX(100%); opacity: 0; }
        }
      `;
      document.head.appendChild(style);
    }
  }

  // Function to export chart data as CSV
  function exportToCSV() {
    try {
      if (!window.currentChart || !window.currentChart.data) {
        showErrorMessage('No chart data available to export');
        return;
      }

      const chartType = getCurrentChartType();
      const labels = window.currentChart.data.labels;
      const data = window.currentChart.data.datasets[0].data;
      const date = getFormattedDate();
      
      let csvContent = "data:text/csv;charset=utf-8,";
      
      // Add title and metadata
      csvContent += `Patient Statistics Report - ${chartType.charAt(0).toUpperCase() + chartType.slice(1)}\n`;
      csvContent += `Generated on: ${new Date().toLocaleString()}\n`;
      csvContent += `\n`;
      
      // Add headers
      csvContent += "Period,Patient Count\n";
      
      // Add data rows
      labels.forEach((label, index) => {
        csvContent += `"${label}",${data[index]}\n`;
      });
      
      // Add summary
      const total = data.reduce((sum, val) => sum + val, 0);
      const average = (total / data.length).toFixed(1);
      csvContent += `\n`;
      csvContent += `Total Patients,${total}\n`;
      csvContent += `Average per Period,${average}\n`;
      
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", `patient_statistics_${chartType}_${date}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      showSuccessMessage('CSV file downloaded successfully!');
    } catch (error) {
      console.error('CSV Export Error:', error);
      showErrorMessage('Failed to export CSV file');
    }
  }

  // Function to copy data to clipboard
  function copyToClipboard() {
    try {
      if (!window.currentChart || !window.currentChart.data) {
        showErrorMessage('No chart data available to copy');
        return;
      }

      const chartType = getCurrentChartType();
      const labels = window.currentChart.data.labels;
      const data = window.currentChart.data.datasets[0].data;
      
      let text = `Patient Statistics - ${chartType.charAt(0).toUpperCase() + chartType.slice(1)}\n`;
      text += `Generated: ${new Date().toLocaleString()}\n\n`;
      text += "Period\tPatient Count\n";
      
      labels.forEach((label, index) => {
        text += `${label}\t${data[index]}\n`;
      });
      
      // Add summary
      const total = data.reduce((sum, val) => sum + val, 0);
      const average = (total / data.length).toFixed(1);
      text += `\nTotal Patients\t${total}\n`;
      text += `Average per Period\t${average}`;
      
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
          showSuccessMessage('Data copied to clipboard!');
        }).catch(() => {
          // Fallback for older browsers
          const textArea = document.createElement('textarea');
          textArea.value = text;
          textArea.style.position = 'fixed';
          textArea.style.left = '-999999px';
          textArea.style.top = '-999999px';
          document.body.appendChild(textArea);
          textArea.focus();
          textArea.select();
          document.execCommand('copy');
          document.body.removeChild(textArea);
          showSuccessMessage('Data copied to clipboard!');
        });
      } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showSuccessMessage('Data copied to clipboard!');
      }
    } catch (error) {
      console.error('Copy Error:', error);
      showErrorMessage('Failed to copy data to clipboard');
    }
  }

  // Function to export to Excel
  function exportToExcel() {
    try {
      if (!window.currentChart || !window.currentChart.data) {
        showErrorMessage('No chart data available to export');
        return;
      }

      const chartType = getCurrentChartType();
      const labels = window.currentChart.data.labels;
      const data = window.currentChart.data.datasets[0].data;
      const date = getFormattedDate();
      
      // Create Excel-compatible CSV content
      let excelContent = "data:application/vnd.ms-excel;charset=utf-8,";
      
      // Add title and metadata
      excelContent += `Patient Statistics Report - ${chartType.charAt(0).toUpperCase() + chartType.slice(1)}\n`;
      excelContent += `Generated on: ${new Date().toLocaleString()}\n`;
      excelContent += `\n`;
      
      // Add headers
      excelContent += "Period\tPatient Count\n";
      
      // Add data rows
      labels.forEach((label, index) => {
        excelContent += `${label}\t${data[index]}\n`;
      });
      
      // Add summary
      const total = data.reduce((sum, val) => sum + val, 0);
      const average = (total / data.length).toFixed(1);
      excelContent += `\n`;
      excelContent += `Total Patients\t${total}\n`;
      excelContent += `Average per Period\t${average}\n`;
      
      const encodedUri = encodeURI(excelContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", `patient_statistics_${chartType}_${date}.xls`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      showSuccessMessage('Excel file downloaded successfully!');
    } catch (error) {
      console.error('Excel Export Error:', error);
      showErrorMessage('Failed to export Excel file');
    }
  }

  // Function to export to PDF
  function exportToPDF() {
    try {
      if (!window.currentChart || !window.jsPDF) {
        showErrorMessage('PDF export not available');
        return;
      }

      const chartType = getCurrentChartType();
      const date = getFormattedDate();
      const canvas = document.getElementById('patientChart');
      
      if (!canvas) {
        showErrorMessage('Chart not found for PDF export');
        return;
      }

      // Create PDF
      const { jsPDF } = window.jsPDF;
      const pdf = new jsPDF('landscape', 'mm', 'a4');
      
      // Add title
      pdf.setFontSize(16);
      pdf.text(`Patient Statistics Report - ${chartType.charAt(0).toUpperCase() + chartType.slice(1)}`, 20, 20);
      
      pdf.setFontSize(12);
      pdf.text(`Generated on: ${new Date().toLocaleString()}`, 20, 30);
      
      // Add chart image
      const imgData = canvas.toDataURL('image/png', 1.0);
      const pdfWidth = pdf.internal.pageSize.getWidth();
      const pdfHeight = pdf.internal.pageSize.getHeight();
      
      // Calculate dimensions to fit the chart properly
      const chartWidth = pdfWidth - 40; // 20mm margin on each side
      const chartHeight = (canvas.height * chartWidth) / canvas.width;
      
      // Position chart
      const yPosition = 40;
      
      if (chartHeight > pdfHeight - yPosition - 20) {
        // If chart is too tall, scale it down
        const scaledHeight = pdfHeight - yPosition - 20;
        const scaledWidth = (canvas.width * scaledHeight) / canvas.height;
        pdf.addImage(imgData, 'PNG', 20, yPosition, scaledWidth, scaledHeight);
      } else {
        pdf.addImage(imgData, 'PNG', 20, yPosition, chartWidth, chartHeight);
      }
      
      // Add data table if there's space
      const labels = window.currentChart.data.labels;
      const data = window.currentChart.data.datasets[0].data;
      const total = data.reduce((sum, val) => sum + val, 0);
      const average = (total / data.length).toFixed(1);
      
      let tableY = yPosition + chartHeight + 20;
      if (tableY < pdfHeight - 40) {
        pdf.setFontSize(10);
        pdf.text('Summary:', 20, tableY);
        tableY += 10;
        pdf.text(`Total Patients: ${total}`, 20, tableY);
        tableY += 7;
        pdf.text(`Average per Period: ${average}`, 20, tableY);
      }
      
      pdf.save(`patient_statistics_${chartType}_${date}.pdf`);
      showSuccessMessage('PDF file downloaded successfully!');
    } catch (error) {
      console.error('PDF Export Error:', error);
      showErrorMessage('Failed to export PDF file');
    }
  }

  // Function to print chart
  function printChart() {
    try {
      const canvas = document.getElementById('patientChart');
      if (!canvas) {
        showErrorMessage('Chart not found for printing');
        return;
      }

      const chartType = getCurrentChartType();
      const imgData = canvas.toDataURL('image/png', 1.0);
      
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Patient Statistics Report</title>
          <style>
            body {
              font-family: Arial, sans-serif;
              margin: 20px;
              text-align: center;
            }
            .header {
              margin-bottom: 20px;
            }
            .chart-container {
              margin: 20px 0;
            }
            .chart-container img {
              max-width: 100%;
              height: auto;
            }
            .footer {
              margin-top: 20px;
              font-size: 12px;
              color: #666;
            }
            @media print {
              body { margin: 0; }
              .no-print { display: none; }
            }
          </style>
        </head>
        <body>
          <div class="header">
            <h1>Patient Statistics Report</h1>
            <h2>${chartType.charAt(0).toUpperCase() + chartType.slice(1)} View</h2>
            <p>Generated on: ${new Date().toLocaleString()}</p>
          </div>
          <div class="chart-container">
            <img src="${imgData}" alt="Patient Statistics Chart" />
          </div>
          <div class="footer">
            <p>Mamatid Health Center System</p>
          </div>
        </body>
        </html>
      `);
      
      printWindow.document.close();
      
      // Wait for image to load before printing
      printWindow.onload = function() {
        setTimeout(() => {
          printWindow.print();
          printWindow.close();
        }, 500);
      };
      
      showSuccessMessage('Print dialog opened!');
    } catch (error) {
      console.error('Print Error:', error);
      showErrorMessage('Failed to print chart');
    }
  }

  // Initialize export functions when DOM is loaded
  document.addEventListener('DOMContentLoaded', function() {
    // Add toast styles
    addToastStyles();

    // Add event listeners to buttons with error handling
    const buttons = [
      { id: 'btnCopy', handler: copyToClipboard },
      { id: 'btnCSV', handler: exportToCSV },
      { id: 'btnExcel', handler: exportToExcel },
      { id: 'btnPDF', handler: exportToPDF },
      { id: 'btnPrint', handler: printChart }
    ];

    buttons.forEach(button => {
      const element = document.getElementById(button.id);
      if (element) {
        element.addEventListener('click', function(e) {
          e.preventDefault();
          button.handler();
        });
      } else {
        console.warn(`Export button ${button.id} not found`);
      }
    });

    console.log('Export functions initialized successfully');
  });

})(); 
