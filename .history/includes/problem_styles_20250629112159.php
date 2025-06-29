<?php
/**
 * Styles CSS pour la page problème
 */
function getProblemStyles() {
    return "
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            line-height: 1.6;
        }

        .problem-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .problem-header {
            padding: 30px;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
        }

        .problem-title {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 28px;
            padding-right: 40px;
        }

        .problem-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #f0f4f8;
        }

        .difficulty {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .difficulty.easy { background: #d5f5e3; color: #27ae60; }
        .difficulty.medium { background: #fef9e7; color: #f39c12; }
        .difficulty.hard { background: #fdedec; color: #e74c3c; }

        .problem-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .tag {
            background: #ecf0f1;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #2c3e50;
        }

        .section {
            padding: 30px;
            border-bottom: 1px solid #f0f0f0;
        }

        .section-title {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #3498db;
        }

        .code-block {
            background: #282c34;
            color: #abb2bf;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', Courier, monospace;
            line-height: 1.5;
            margin: 15px 0;
            position: relative;
        }

        .solutions-section {
            padding: 30px;
        }

        .solution-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .solution-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .solution-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .solution-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .solution-date {
            color: #7f8c8d;
            font-size: 12px;
        }

        .actions {
            padding: 30px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-success {
            background: #2ecc71;
            color: white;
        }

        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-outline {
            background: transparent;
            color: #3498db;
            border: 2px solid #3498db;
        }

        .btn-outline:hover {
            background: #ebf5fb;
        }

        .favorite-btn {
            position: absolute;
            top: 30px;
            right: 30px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s;
            color: #ddd;
        }

        .favorite-btn.active {
            color: #f1c40f;
        }

        .favorite-btn:hover {
            transform: scale(1.2);
        }

        .problem-stats {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }

        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
        }

        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            background: rgba(52, 152, 219, 0.2);
        }

        .no-solutions {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .user-solution-status {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .user-solution-status.pending {
            background: #fff3cd;
            border-color: #ffeaa7;
        }

        .user-solution-status.rejected {
            background: #f8d7da;
            border-color: #f5c6cb;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .problem-container {
                margin: 20px 10px;
                border-radius: 8px;
            }
          
            .problem-header, .section, .solutions-section, .actions {
                padding: 15px;
            }
          
            .actions {
                flex-direction: column;
            }
          
            .btn {
                width: 100%;
                justify-content: center;
            }
          
            .problem-meta {
                flex-direction: column;
                gap: 8px;
            }
        }
    ";
}
?>