<template>
  <div class="historique">
    <HistoriqueConges />
  </div>
</template>

<script>
import HistoriqueConges from "@/components/dashboard/HistoriqueConges.vue";

export default {
  name: "HistoriqueView",
  components: {
    HistoriqueConges,

    
  },
};
</script>

<style scoped>
.historique {
  padding: 2rem;
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: "Inter", sans-serif;
}

h1 {
  color: #2c3e50;
  margin-bottom: 2rem;
  font-size: 2rem;
  font-weight: 600;
  position: relative;
  text-align: center;
}

h1:after {
  content: "";
  position: absolute;
  bottom: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 100px;
  height: 3px;
  background: var(--gradient-primary);
  border-radius: 2px;
}

.historique-filters {
  max-width: 800px;
  margin: 0 auto 2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.search-box {
  position: relative;
  width: 100%;
}

.search-box input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.3s ease;
}

.search-box input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.filter-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  background: white;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-btn:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.filter-btn.active {
  background: var(--gradient-primary);
  color: white;
  border-color: transparent;
}

.historique-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 800px;
  margin: 0 auto;
}

.historique-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.historique-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.historique-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: var(--gradient-primary);
}

.historique-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.historique-dates {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
}

.date-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #64748b;
}

.date-item i {
  color: var(--color-primary);
  font-size: 1.1rem;
}

.historique-motif {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #2c3e50;
  font-weight: 500;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.historique-motif i {
  color: var(--color-primary);
  font-size: 1.1rem;
}

.historique-actions {
  display: flex;
  justify-content: flex-end;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 8px;
  border: none;
  background: var(--gradient-primary);
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.badge-primary {
  background: var(--color-primary-light);
  color: var(--color-primary);
}

.badge-warning {
  background: var(--color-warning-light);
  color: var(--color-warning);
}

.badge-info {
  background: var(--color-info-light);
  color: var(--color-info);
}

.badge-secondary {
  background: #f1f5f9;
  color: #64748b;
}

.status {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-success {
  background: var(--color-success-light);
  color: var(--color-success);
}

.status-warning {
  background: var(--color-warning-light);
  color: var(--color-warning);
}

.status-error {
  background: var(--color-error-light);
  color: var(--color-error);
}

.status-secondary {
  background: #f1f5f9;
  color: #64748b;
}

@media (max-width: 768px) {
  .historique {
    padding: 1rem;
  }

  .historique-card {
    padding: 1rem;
  }

  .historique-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .filter-buttons {
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }

  .filter-btn {
    white-space: nowrap;
  }
}

/* Variables CSS pour les couleurs et gradients */
:root {
  --color-primary: #008a9b;
  --color-primary-light: #e6f7ff;
  --color-warning: #d97706;
  --color-warning-light: #fff7e6;
  --color-success: #16a34a;
  --color-success-light: #f0fdf4;
  --color-error: #dc2626;
  --color-error-light: #fef2f2;
  --color-info: #0284c7;
  --color-info-light: #e6f7ff;
  --gradient-primary: linear-gradient(135deg, #008a9b, #00b4d8);
}
</style>
