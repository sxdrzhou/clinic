// 患者详情模态框相关元素
const modal = document.getElementById("patientModal");
const closeBtn = document.querySelector(".close-btn");
const patientInfo = document.getElementById("patientInfo");
const visitRecords = document.getElementById("visitRecords");

// 关闭模态框
closeBtn.onclick = function() {
    modal.style.display = "none";
}

// 点击模态框外部关闭
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// 改进的选定患者处理函数
function selectPatient(patientId) {
    // 显示模态框
    modal.style.display = "block";
    // 显示加载状态
    patientInfo.innerHTML = "<p>加载中...</p>";
    visitRecords.innerHTML = "<p>加载中...</p>";

    // 记录请求到控制台，便于调试
    console.log(`请求患者详情: ${patientId}`);

    // 通过AJAX获取患者详情和就诊记录
    fetch('api/patient_query_api.php?patient_id=' + encodeURIComponent(patientId))
        .then(response => {
            // 检查响应状态
            if (!response.ok) {
                throw new Error(`网络响应错误: ${response.status} ${response.statusText}`);
            }
            
            // 获取响应文本，先检查是否为JSON格式
            return response.text().then(text => {
                try {
                    // 尝试解析为JSON
                    return JSON.parse(text);
                } catch (e) {
                    // 如果解析失败，记录原始响应并抛出错误
                    console.error('响应不是有效的JSON:', text);
                    throw new Error(`数据格式错误: 服务器返回了非JSON数据。请确保PHP服务器正在运行，并且相关API文件存在。`);
                }
            });
        })
        .then(data => {
            if (data.error) {
                // 处理服务器返回的错误
                console.error('服务器错误:', data.error);
                patientInfo.innerHTML = `<p>获取患者信息失败: ${data.error}</p>`;
                visitRecords.innerHTML = "<p>获取就诊记录失败</p>";
            } else {
                // 成功获取数据
                displayPatientDetails(data.patient);
                // 存储就诊记录到全局变量
                window.currentPatientVisits = data.visits;
                displayVisitRecords(data.visits);
            }
        })
        .catch(error => {
            console.error('请求错误:', error);
            // 提供更具体的错误提示
            let errorMsg = error.message;
            if (errorMsg.includes('Unexpected token')) {
                errorMsg = '获取患者信息失败: 服务器返回了无效的JSON数据。请确保PHP服务器正在运行，并且相关API文件存在。';
            }
            patientInfo.innerHTML = `<p>${errorMsg}</p>`;
            visitRecords.innerHTML = "<p>获取就诊记录失败</p>";
        });
}

// 显示患者详情
function displayPatientDetails(patient) {
    // 计算现在年龄
    let currentAge = "未知";
    if (patient.出生日期) {
        // 提取日期部分（去掉时间）
        const birthDateStr = patient.出生日期.split(' ')[0];
        const birthDate = new Date(birthDateStr);
        const today = new Date();

        if (!isNaN(birthDate.getTime())) {
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            // 如果本月还没过生日，年龄减1
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            currentAge = age + "岁";
        }
    }

    let html = "<div class='patient-details'>";
    html += `<div class='detail-item'><span class='detail-label'>姓名:</span> ${patient.姓名 || "未知"}</div>`;
    html += `<div class='detail-item'><span class='detail-label'>性别:</span> ${patient.性别 || "未知"}</div>`;
    html += `<div class='detail-item'><span class='detail-label'>就诊时年龄:</span> ${patient.年龄 || "未知"}</div>`;
    html += `<div class='detail-item'><span class='detail-label'>现在年龄:</span> ${currentAge}</div>`;
    html += `<div class='detail-item'><span class='detail-label'>电话号:</span> ${patient.电话 || "未知"}</div>`;
    // 添加更多患者详情字段（如果有）
    html += "</div>";

    patientInfo.innerHTML = html;
}

// 显示就诊记录
function displayVisitRecords(visits) {
    if (!visits || visits.length === 0) {
        visitRecords.innerHTML = "<p>无就诊记录</p>";
        return;
    }

    // 检测是否为移动设备
    const isMobile = window.innerWidth <= 768;
    let html = '';

    if (isMobile) {
            // 移动端布局 - 使用卡片样式
            html += '<div class="visit-cards">';

            visits.forEach((visit, index) => {
                // 跳过空记录
                if (!visit || !visit.日期 || !visit.流水ID) return;

                html += '<div class="visit-card">';
                html += `<div class="visit-number">${index + 1}</div>`;
                html += `<div class="visit-date">${formatDate(visit.日期)}</div>`;
                html += `<button class='select-btn mobile-select-btn' onclick='selectVisit("${visit.流水ID}")'>选定</button>`;
                html += '</div>';
            });

            html += '</div>';
        } else {
            // 桌面端布局 - 使用表格
            html += "<table class='visit-table'>";
            html += "<tr><th>编号</th><th>日期</th><th>操作</th></tr>";

            visits.forEach((visit, index) => {
                // 跳过空记录
                if (!visit || !visit.日期 || !visit.流水ID) return;

                html += "<tr>";
                html += `<td>${index + 1}</td>`;
                html += `<td>${formatDate(visit.日期)}</td>`;
                html += `<td><button class='select-btn' onclick='selectVisit("${visit.流水ID}")'>选定</button></td>`;
                html += "</tr>";
            });

            html += "</table>";
        }

    visitRecords.innerHTML = html;

    // 添加就诊记录样式
    addVisitRecordsStyles();
}

// 格式化日期为 yyyy年mm月dd日
function formatDate(dateString) {
    if (!dateString) return "未知";

    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "无效日期";

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}年${month}月${day}日`;
}

// 就诊记录选定函数
function selectVisit(visitId) {
    // 打开药品详情模态框
    openMedicationModal(visitId);
}

// 打开药品详情模态框
function openMedicationModal(visitId) {
    console.log('打开药品详情模态框，流水ID:', visitId);
    // 创建药品详情模态框（如果不存在）
    if (!document.getElementById('medicationModal')) {
        createMedicationModal();
    }

    // 显示模态框
    const modal = document.getElementById('medicationModal');
    modal.style.display = 'block';

    // 加载药品数据
    loadMedicationData(visitId);
}

// 创建药品详情模态框
function createMedicationModal() {
    console.log('创建药品详情模态框');
    // 创建模态框元素
    const modal = document.createElement('div');
    modal.id = 'medicationModal';
    modal.className = 'modal';
    modal.style.display = 'none'; // 初始隐藏
    modal.style.position = 'fixed';
    modal.style.zIndex = '3000'; // 确保在患者详情模态框之上
    modal.style.left = '0';
    modal.style.top = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.overflow = 'auto';
    modal.style.backgroundColor = 'rgba(0,0,0,0.4)';

    // 创建模态框内容
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    modalContent.style.backgroundColor = '#fefefe';
    modalContent.style.margin = '10% auto';
    modalContent.style.padding = '20px';
    modalContent.style.border = '1px solid #888';
    modalContent.style.width = '80%';
    modalContent.style.maxWidth = '800px';
    modalContent.style.borderRadius = '8px';
    modalContent.style.boxShadow = '0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19)';

    // 创建关闭按钮（X号）
    const closeBtn = document.createElement('span');
    closeBtn.className = 'close-btn';
    closeBtn.textContent = '×';
    closeBtn.onclick = function() {
        console.log('点击关闭按钮');
        document.getElementById('medicationModal').style.display = 'none';
    };
    closeBtn.style.float = 'right';
    closeBtn.style.fontSize = '28px';
    closeBtn.style.fontWeight = 'bold';
    closeBtn.style.color = '#aaa';
    closeBtn.style.cursor = 'pointer';
    closeBtn.onmouseover = function() {
        closeBtn.style.color = 'black';
    };
    closeBtn.onmouseout = function() {
        closeBtn.style.color = '#aaa';
    };

    // 创建标题
    const title = document.createElement('h2');
    title.textContent = '药品详情';

    // 创建药品信息容器
    const medicationInfo = document.createElement('div');
    medicationInfo.id = 'medicationInfo';

    // 组装模态框
    modalContent.appendChild(closeBtn);
    modalContent.appendChild(title);
    modalContent.appendChild(medicationInfo);
    modal.appendChild(modalContent);

    // 添加到文档
    document.body.appendChild(modal);
    console.log('药品详情模态框已添加到文档');
}

// 存储当前药品数据，用于窗口大小变化时重新渲染
let currentMedications = null;

// 加载药品数据
function loadMedicationData(visitId) {
    console.log('加载药品数据，请求URL:', `api/medication_details_api.php?visitId=${encodeURIComponent(visitId)}`);
    const medicationInfo = document.getElementById('medicationInfo');
    medicationInfo.innerHTML = '<div class="loading">加载中...</div>';

    // 使用fetch API请求药品数据
    fetch(`api/medication_details_api.php?visitId=${encodeURIComponent(visitId)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`网络响应错误: ${response.status} ${response.statusText}`);
            }
            
            // 检查响应内容类型
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // 如果不是JSON，获取原始文本并记录
                return response.text().then(text => {
                    console.error('非JSON响应:', text);
                    throw new Error(`数据格式错误: 服务器返回了非JSON数据 (内容类型: ${contentType})`);
                });
            }
            
            // 尝试解析JSON
            return response.json().catch(error => {
                console.error('JSON解析错误:', error);
                // 获取原始文本以便调试
                return response.text().then(text => {
                    console.error('JSON解析失败的原始文本:', text);
                    throw new Error('数据格式错误: 服务器返回的JSON格式无效');
                });
            });
        })
        .then(data => {
            console.log('药品数据响应:', data);
            if (typeof data !== 'object') {
                throw new Error('数据格式错误: 服务器返回的JSON不是对象类型');
            }
            if (data.success) {
                if (!Array.isArray(data.medications)) {
                    throw new Error('数据格式错误: medications不是数组类型');
                }
                currentMedications = data.medications;
            displayMedicationData(data.medications);
            
            // 添加窗口大小变化监听事件
            window.addEventListener('resize', handleWindowResize);
            } else {
                medicationInfo.innerHTML = `<div class="no-results">${data.error || '未找到药品信息'}</div>`;
            }
        })
        .catch(error => {
            console.error('加载药品数据错误:', error);
            medicationInfo.innerHTML = `<div class="no-results">${error.message || '加载药品信息失败'}</div>`;
        });
}

// 显示药品数据
function displayMedicationData(medications) {
    if (!medications || medications.length === 0) {
        document.getElementById('medicationInfo').innerHTML = '<div class="no-results">未找到药品信息</div>';
        return;
    }

    // 获取中药副数（只取第一个）并转换为整数
    let totalDoses = medications[0]['中药副数'] || '未知';
    if (totalDoses !== '未知') {
        totalDoses = parseInt(totalDoses, 10);
        if (isNaN(totalDoses)) {
            totalDoses = '未知';
        }
    }

    // 检测屏幕宽度
    const isMobile = window.innerWidth <= 768;

    let html = '';

    if (isMobile) {
            // 移动端布局 - 每行显示两种药品
            html += '<div class="medication-cards">';

            // 遍历药品数据，每两个一组
            for (let i = 0; i < medications.length; i += 2) {
                html += '<div class="medication-row">';

                // 第一个药品
                if (i < medications.length) {
                    let medication1 = medications[i];
                    let dosage1 = medication1['申请数量'] || '未知';
                    if (dosage1 !== '未知') {
                        dosage1 = parseInt(dosage1, 10);
                        if (isNaN(dosage1)) {
                            dosage1 = '未知';
                        }
                    }

                    html += '<div class="medication-item">';
                    html += `<div class="card-content">
                        <span class="card-name">${htmlspecialchars(medication1['名称'])}</span>
                        <span class="card-dosage">${htmlspecialchars(dosage1)}${htmlspecialchars(medication1['单位'] || '')}</span>
                    </div>`;
                    html += '</div>';
                }

                // 第二个药品或空白项
                if (i + 1 < medications.length) {
                    let medication2 = medications[i + 1];
                    let dosage2 = medication2['申请数量'] || '未知';
                    if (dosage2 !== '未知') {
                        dosage2 = parseInt(dosage2, 10);
                        if (isNaN(dosage2)) {
                            dosage2 = '未知';
                        }
                    }

                    html += '<div class="medication-item">';
                    html += `<div class="card-content">
                        <span class="card-name">${htmlspecialchars(medication2['名称'])}</span>
                        <span class="card-dosage">${htmlspecialchars(dosage2)}${htmlspecialchars(medication2['单位'] || '')}</span>
                    </div>`;
                    html += '</div>';
                } else {
                    // 当只剩一个药品时，添加空白项
                    html += '<div class="medication-item empty-item">';
                    html += '<div class="card-content"></div>';
                    html += '</div>';
                }

                html += '</div>';
            }

            html += '</div>';
            html += `<div class="total-doses" style="text-align: right;">副数: ${totalDoses}</div>`;
        } else {
        // 桌面端布局 - 使用表格
        html += '<table class="medication-table">';
        html += '<thead><tr><th>药品名称</th><th>剂量</th><th>药品名称</th><th>剂量</th></tr></thead>';
        html += '<tbody>';

        // 遍历药品数据，每两个一组
        for (let i = 0; i < medications.length; i += 2) {
            html += '<tr>';

            // 第一个药品
            if (i < medications.length) {
                let medication1 = medications[i];
                let dosage1 = medication1['申请数量'] || '未知';
                if (dosage1 !== '未知') {
                    dosage1 = parseInt(dosage1, 10);
                    if (isNaN(dosage1)) {
                        dosage1 = '未知';
                    }
                }
                html += '<td>' + htmlspecialchars(medication1['名称']) + '</td>';
                html += '<td>' + htmlspecialchars(dosage1) + htmlspecialchars(medication1['单位'] || '') + '</td>';
            } else {
                html += '<td></td><td></td>';
            }

            // 第二个药品
            if (i + 1 < medications.length) {
                let medication2 = medications[i + 1];
                let dosage2 = medication2['申请数量'] || '未知';
                if (dosage2 !== '未知') {
                    dosage2 = parseInt(dosage2, 10);
                    if (isNaN(dosage2)) {
                        dosage2 = '未知';
                    }
                }
                html += '<td>' + htmlspecialchars(medication2['名称']) + '</td>';
                html += '<td>' + htmlspecialchars(dosage2) + htmlspecialchars(medication2['单位'] || '') + '</td>';
            } else {
                html += '<td></td><td></td>';
            }

            html += '</tr>';
        }

        html += '</tbody>';
        html += '</table>';
        html += `<div class="total-doses" style="text-align: right;">副数: ${totalDoses}</div>`;
    }

    document.getElementById('medicationInfo').innerHTML = html;

    // 添加移动端样式
    if (isMobile) {
        addMobileStyles();
    }
}

// 添加移动端样式
function addMobileStyles() {
    // 检查是否已添加样式
    if (!document.getElementById('mobile-medication-styles')) {
        const style = document.createElement('style');
        style.id = 'mobile-medication-styles';
        style.textContent = `
            .medication-cards {
                display: flex;
                flex-direction: column;
                gap: 0;
                width: 100%;
                box-sizing: border-box;
            }
            .medication-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                width: 100%;
            }
            .medication-item {
                flex: 1 0 calc(50% - 5px); /* 两个项目每行，减去间距 */
                min-width: 150px; /* 确保在小屏幕上也有足够宽度 */
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 5px; /* 设置上下左右边距为5 */
                background-color: #fff;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                box-sizing: border-box;
            }
            .medication-item.empty-item {
                border: 1px dashed #ddd;
                background-color: #f9f9f9;
                box-shadow: none;
            }
            .card-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }
            .card-name {
                font-weight: bold;
                flex: 1;
            }
            .card-dosage {
                color: #555;
                margin-left: 3px; /* 调整为原来的30% */
                white-space: nowrap;
            }
            /* 确保所有移动设备上都是每行2组药品 */
            @media (max-width: 768px) {
                .medication-item {
                    flex: 1 0 calc(50% - 5px);
                    min-width: 120px; /* 确保在小屏幕上也能显示2列 */
                }
            }
            .total-doses {
                margin-top: 4.5px; /* 调整为原来的30% */
                font-weight: bold;
                text-align: right;
            }
        `;
        document.head.appendChild(style);
    }
}

// 处理窗口大小变化
function handleWindowResize() {
    // 重新计算isMobile的值
    isMobile = window.innerWidth <= 768; // 扩大移动设备的判断范围
    if (currentMedications) {
        displayMedicationData(currentMedications);
    }
}

// 添加就诊记录样式
function addVisitRecordsStyles() {
    // 检查是否已添加样式
    if (!document.getElementById('visit-records-styles')) {
        const style = document.createElement('style');
        style.id = 'visit-records-styles';
        style.textContent = `
            /* 移动端就诊记录样式 */
            .visit-cards {
                display: flex;
                flex-direction: column;
                gap: 10px;
                width: 100%;
                box-sizing: border-box;
            }
            .visit-card {
                display: flex;
                align-items: center;
                padding: 10px;
                background-color: #fff;
                box-sizing: border-box;
                /* 移除框线 */
                border: none;
            }
            .visit-number {
                font-weight: bold;
                width: 10%;
            }
            .visit-date {
                width: 60%;
                padding: 0 10px;
            }
            .select-btn {
                width: 30%;
                box-sizing: border-box;
            }
            /* 桌面端就诊记录样式 */
            .visit-table {
                width: 100%;
                border-collapse: collapse;
            }
            .visit-table th,
            .visit-table td {
                padding: 8px;
                text-align: left;
                /* 移除框线 */
                border: none;
            }
            .visit-table th {
                background-color: #f2f2f2;
            }
            .visit-table tr {
                /* 添加轻微的分隔效果 */
                border-bottom: 1px solid #f2f2f2;
            }
            .select-btn {
                background-color: #2196F3; /* 与patient_query.php中保持一致的蓝色背景 */
                color: white;
                border: none;
                text-align: center;
                text-decoration: none;
                font-size: 14px;
                cursor: pointer;
                border-radius: 4px;
                /* 桌面端样式 - 充满整个单元格并留出8px的内边距 */
                width: 100%;
                height: 100%;
                padding: 8px;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
            }
            .select-btn:hover {
                background-color: #0b7dda; /* 与patient_query.php中保持一致的悬停颜色 */
            }
            
            /* 移动端样式 - 保持与桌面端一致的颜色和交互效果 */
            @media (max-width: 768px) {
                .select-btn,
                .mobile-select-btn {
                    background-color: #2196F3 !important; /* 使用!important确保优先级 */
                    color: white !important;
                    width: 30% !important;
                    height: auto !important;
                    padding: 5px 10px !important;
                    margin: 2px 2px !important;
                    display: inline-block !important;
                    border: none !important;
                    border-radius: 4px !important;
                    font-size: 14px !important;
                    text-align: center !important;
                    box-sizing: border-box !important;
                    transition: none !important; /* 禁用过渡效果 */
                }
                
                /* 确保所有状态下的样式一致 */
                .select-btn,
                .mobile-select-btn,
                .select-btn:hover,
                .select-btn:active,
                .mobile-select-btn:hover,
                .mobile-select-btn:active {
                    background-color: #2196F3 !important; /* 统一使用蓝色背景 */
                    color: white !important;
                    width: 30% !important;
                    height: auto !important;
                    padding: 5px 10px !important;
                    margin: 2px 2px !important;
                    display: inline-block !important;
                    border: none !important;
                    border-radius: 4px !important;
                    font-size: 14px !important;
                    text-align: center !important;
                    box-sizing: border-box !important;
                    transition: none !important; /* 禁用过渡效果 */
                }
                
                /* 悬停和点击状态保持相同的蓝色 */
                .select-btn:hover,
                .select-btn:active,
                .mobile-select-btn:hover,
                .mobile-select-btn:active {
                    background-color: #2196F3 !important;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

// HTML特殊字符转义
function htmlspecialchars(str) {
    if (str === null || str === undefined) {
        return '';
    }
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// 初始化函数
function initPatientQuery() {
    // 确保在页面加载完成后执行
    document.addEventListener('DOMContentLoaded', function() {
        // 如果已有患者详情模态框，为其添加关闭事件
        const patientModal = document.getElementById('patientModal');
        if (patientModal) {
            const closeBtn = patientModal.querySelector('.close-btn');
            if (closeBtn) {
                closeBtn.onclick = function() {
                    patientModal.style.display = 'none';
                };
            }

            // 点击模态框外部关闭
            window.onclick = function(event) {
                if (event.target === patientModal) {
                    patientModal.style.display = 'none';
                } else if (event.target === document.getElementById('medicationModal')) {
                    document.getElementById('medicationModal').style.display = 'none';
                }
            };
        }
    });
}

// 显示环境警告


// 处理窗口大小变化时重新渲染就诊记录
function handleWindowResizeForVisits() {
    // 获取患者详情模态框
    const patientModal = document.getElementById('patientModal');
    if (patientModal && patientModal.style.display === 'block') {
        // 如果模态框是打开的，尝试重新渲染就诊记录
        const visitRecordsElement = document.getElementById('visitRecords');
        if (visitRecordsElement && visitRecordsElement.innerHTML !== '<p>无就诊记录</p>' && visitRecordsElement.innerHTML !== '<p>加载中...</p>') {
            // 假设我们有一个全局变量存储当前患者的就诊记录
            if (window.currentPatientVisits) {
                displayVisitRecords(window.currentPatientVisits);
            }
        }
    }
}

// 添加窗口大小变化监听事件以适应就诊记录显示
window.addEventListener('resize', handleWindowResizeForVisits);

// 页面加载完成后初始化
window.addEventListener('load', initPatientQuery);