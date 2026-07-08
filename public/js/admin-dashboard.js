/**
 * MERJ Learn — Dashboard administration (Chart.js)
 */
(function () {
  'use strict';

  const dataEl = document.getElementById('admin-dashboard-data');
  if (!dataEl || typeof Chart === 'undefined') return;

  const mode = dataEl.dataset.dashboardMode || 'super_admin';

  let charts;
  try {
    charts = JSON.parse(dataEl.textContent || '{}');
  } catch (e) {
    console.error('Dashboard charts data invalid', e);
    return;
  }

  const brand = '#155E75';
  const brandLight = 'rgba(21, 94, 117, 0.15)';
  const violet = '#7C3AED';
  const violetLight = 'rgba(124, 58, 237, 0.15)';
  const ok = '#059669';
  const okLight = 'rgba(5, 150, 105, 0.15)';
  const palette = ['#155E75', '#7C3AED', '#059669', '#D97706', '#DC2626', '#2563EB'];
  const gradePalette = ['#DC2626', '#D97706', '#EAB308', '#059669', '#155E75'];

  Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
  Chart.defaults.color = '#64748B';
  Chart.defaults.plugins.legend.display = false;

  const defaultLineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    scales: {
      x: {
        grid: { display: false },
        ticks: { maxRotation: 0 },
      },
      y: {
        beginAtZero: true,
        ticks: { precision: 0 },
        grid: { color: 'rgba(148, 163, 184, 0.2)' },
      },
    },
  };

  const defaultBarOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      x: {
        grid: { display: false },
        ticks: { maxRotation: 25, autoSkip: false, font: { size: 11 } },
      },
      y: {
        beginAtZero: true,
        grid: { color: 'rgba(148, 163, 184, 0.2)' },
      },
    },
  };

  function initStudentsByClassChart() {
    const canvas = document.getElementById('chart-students-class');
    if (!canvas) return;

    const students = charts.studentsByClass || { labels: [], values: [] };

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: students.labels,
        datasets: [{
          label: 'Étudiants',
          data: students.values,
          backgroundColor: palette.map((c) => c + 'CC'),
          borderColor: palette,
          borderWidth: 1,
          borderRadius: 6,
        }],
      },
      options: {
        ...defaultBarOptions,
        scales: {
          ...defaultBarOptions.scales,
          y: {
            ...defaultBarOptions.scales.y,
            ticks: { precision: 0 },
          },
        },
      },
    });
  }

  if (mode === 'super_admin') {
    const activity = charts.activity || { labels: [], messages: [], forum: [], logins: [] };
    const activityCanvas = document.getElementById('chart-activity');

    if (activityCanvas) {
      new Chart(activityCanvas, {
        type: 'line',
        data: {
          labels: activity.labels,
          datasets: [
            {
              label: 'Messages',
              data: activity.messages,
              borderColor: brand,
              backgroundColor: brandLight,
              fill: true,
              tension: 0.35,
              pointRadius: 4,
              pointHoverRadius: 6,
            },
            {
              label: 'Forum',
              data: activity.forum,
              borderColor: violet,
              backgroundColor: violetLight,
              fill: true,
              tension: 0.35,
              pointRadius: 4,
              pointHoverRadius: 6,
            },
            {
              label: 'Connexions',
              data: activity.logins,
              borderColor: ok,
              backgroundColor: okLight,
              fill: true,
              tension: 0.35,
              pointRadius: 4,
              pointHoverRadius: 6,
            },
          ],
        },
        options: defaultLineOptions,
      });
    }

    initStudentsByClassChart();

    const rolesCanvas = document.getElementById('chart-roles');
    if (rolesCanvas) {
      const roles = charts.roles || { labels: [], values: [] };

      new Chart(rolesCanvas, {
        type: 'doughnut',
        data: {
          labels: roles.labels,
          datasets: [{
            data: roles.values,
            backgroundColor: palette.slice(0, roles.labels.length),
            borderWidth: 2,
            borderColor: '#fff',
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '62%',
          plugins: {
            legend: {
              display: true,
              position: 'bottom',
              labels: { boxWidth: 12, padding: 14 },
            },
          },
        },
      });
    }

    return;
  }

  if (mode === 'school_admin') {
    const notesActivity = charts.notesActivity || { labels: [], values: [] };
    const notesCanvas = document.getElementById('chart-notes-activity');

    if (notesCanvas) {
      new Chart(notesCanvas, {
        type: 'line',
        data: {
          labels: notesActivity.labels,
          datasets: [{
            label: 'Notes',
            data: notesActivity.values,
            borderColor: brand,
            backgroundColor: brandLight,
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
          }],
        },
        options: defaultLineOptions,
      });
    }

    initStudentsByClassChart();

    const gradesCanvas = document.getElementById('chart-grades-class');
    if (gradesCanvas) {
      const grades = charts.gradesByClass || { labels: [], values: [] };

      new Chart(gradesCanvas, {
        type: 'bar',
        data: {
          labels: grades.labels,
          datasets: [{
            label: 'Moyenne /20',
            data: grades.values,
            backgroundColor: ok + 'CC',
            borderColor: ok,
            borderWidth: 1,
            borderRadius: 6,
          }],
        },
        options: {
          ...defaultBarOptions,
          scales: {
            ...defaultBarOptions.scales,
            y: {
              ...defaultBarOptions.scales.y,
              max: 20,
              ticks: { stepSize: 2 },
            },
          },
        },
      });
    }

    const distributionCanvas = document.getElementById('chart-grade-distribution');
    if (distributionCanvas) {
      const distribution = charts.gradeDistribution || { labels: [], values: [] };

      new Chart(distributionCanvas, {
        type: 'doughnut',
        data: {
          labels: distribution.labels,
          datasets: [{
            data: distribution.values,
            backgroundColor: gradePalette.slice(0, distribution.labels.length),
            borderWidth: 2,
            borderColor: '#fff',
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '58%',
          plugins: {
            legend: {
              display: true,
              position: 'bottom',
              labels: { boxWidth: 12, padding: 10, font: { size: 11 } },
            },
          },
        },
      });
    }
  }
})();
