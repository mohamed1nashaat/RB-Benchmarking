import jsPDF from 'jspdf'
import * as XLSX from 'xlsx'
import { saveAs } from 'file-saver'
import html2canvas from 'html2canvas'

export interface ExportData {
  title: string
  data: any[]
  headers?: string[]
  filename?: string
}

export const exportToPDF = async (elementId: string, filename = 'benchmark-report.pdf') => {
  try {
    const element = document.getElementById(elementId)
    if (!element) {
      throw new Error('Element not found')
    }

    // Create canvas from HTML element
    const canvas = await html2canvas(element, {
      scale: 2,
      useCORS: true,
      allowTaint: true,
      backgroundColor: '#ffffff'
    })

    const imgData = canvas.toDataURL('image/png')
    const pdf = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4'
    })

    const imgWidth = 210 // A4 width in mm
    const pageHeight = 295 // A4 height in mm
    const imgHeight = (canvas.height * imgWidth) / canvas.width
    let heightLeft = imgHeight

    let position = 0

    // Add first page
    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight)
    heightLeft -= pageHeight

    // Add additional pages if needed
    while (heightLeft >= 0) {
      position = heightLeft - imgHeight
      pdf.addPage()
      pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight)
      heightLeft -= pageHeight
    }

    pdf.save(filename)
    return true
  } catch (error) {
    console.error('PDF export failed:', error)
    return false
  }
}

export const exportToExcel = (data: ExportData[], filename = 'benchmark-data.xlsx') => {
  try {
    const workbook = XLSX.utils.book_new()

    data.forEach((sheet, index) => {
      let worksheet: XLSX.WorkSheet

      if (sheet.headers && Array.isArray(sheet.data)) {
        // Create worksheet with headers
        worksheet = XLSX.utils.json_to_sheet(sheet.data, { header: sheet.headers })
      } else if (Array.isArray(sheet.data)) {
        // Create worksheet from array of objects
        worksheet = XLSX.utils.json_to_sheet(sheet.data)
      } else {
        // Create worksheet from object
        worksheet = XLSX.utils.json_to_sheet([sheet.data])
      }

      // Set column widths
      const cols = []
      if (sheet.headers) {
        for (let i = 0; i < sheet.headers.length; i++) {
          cols.push({ wch: 15 })
        }
        worksheet['!cols'] = cols
      }

      const sheetName = sheet.title || `Sheet${index + 1}`
      XLSX.utils.book_append_sheet(workbook, worksheet, sheetName)
    })

    // Generate buffer and save
    const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' })
    const blob = new Blob([excelBuffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    saveAs(blob, filename)
    return true
  } catch (error) {
    console.error('Excel export failed:', error)
    return false
  }
}

export const exportToCSV = (data: any[], filename = 'benchmark-data.csv', headers?: string[]) => {
  try {
    let csvContent = ''

    // Add headers if provided
    if (headers) {
      csvContent += headers.join(',') + '\n'
    }

    // Add data rows
    data.forEach(row => {
      if (typeof row === 'object') {
        const values = headers 
          ? headers.map(header => row[header] || '')
          : Object.values(row)
        csvContent += values.map(value => `"${value}"`).join(',') + '\n'
      } else {
        csvContent += `"${row}"\n`
      }
    })

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    saveAs(blob, filename)
    return true
  } catch (error) {
    console.error('CSV export failed:', error)
    return false
  }
}

export const formatDataForExport = (
  industryBenchmarks: any,
  trendingData: any,
  summary: any
) => {
  const exportData: ExportData[] = []

  // Summary data
  exportData.push({
    title: 'Summary',
    headers: ['Metric', 'Value'],
    data: [
      { Metric: 'Total Industries', Value: summary.total_industries },
      { Metric: 'Total Accounts', Value: summary.total_accounts },
      { Metric: 'Total Spend', Value: summary.total_spend },
      { Metric: 'Total Leads', Value: summary.total_leads }
    ]
  })

  // Trending metrics data
  if (trendingData) {
    exportData.push({
      title: 'Trending Metrics',
      headers: ['Metric', 'Your Average', 'Industry Average', 'Top Performers'],
      data: [{
        Metric: 'Current Trend',
        'Your Average': trendingData.your_avg,
        'Industry Average': trendingData.industry_avg,
        'Top Performers': trendingData.top_performers_avg
      }]
    })
  }

  // Industry benchmarks data
  if (industryBenchmarks) {
    const benchmarkData = []
    Object.entries(industryBenchmarks).forEach(([industry, data]: [string, any]) => {
      Object.entries(data.metrics || {}).forEach(([metric, metricData]: [string, any]) => {
        benchmarkData.push({
          Industry: industry,
          Metric: metric,
          'Your Performance': metricData.actual,
          'Industry Average': metricData.benchmark?.avg,
          'Performance Score': metricData.performance,
          Status: metricData.status
        })
      })
    })

    if (benchmarkData.length > 0) {
      exportData.push({
        title: 'Industry Benchmarks',
        headers: ['Industry', 'Metric', 'Your Performance', 'Industry Average', 'Performance Score', 'Status'],
        data: benchmarkData
      })
    }
  }

  return exportData
}