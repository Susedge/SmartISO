    
    public function getDepartments()
    {
        $departments = $this->departmentModel->orderBy('code', 'ASC')->findAll();
        return $this->response->setJSON([
            'success' => true,
            'departments' => $departments
        ]);
    }
    
    public function getOffices()
    {
        $offices = $this->officeModel->orderBy('code', 'ASC')->findAll();
        return $this->response->setJSON([
            'success' => true,
            'offices' => $offices
        ]);
    }
