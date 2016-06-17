/**
 * PreviewArticlesAtWorkspace.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class PreviewArticlesAtWorkspace  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String workspaceId;

    private com.woodwing.enterprise.interfaces.services.wfl.ArticleAtWorkspace[] articles;

    private java.lang.String action;

    private java.lang.String layoutId;

    private java.lang.String editionId;

    private com.woodwing.enterprise.interfaces.services.wfl.PreviewType previewType;

    private java.lang.String[] requestInfo;

    public PreviewArticlesAtWorkspace() {
    }

    public PreviewArticlesAtWorkspace(
           java.lang.String ticket,
           java.lang.String workspaceId,
           com.woodwing.enterprise.interfaces.services.wfl.ArticleAtWorkspace[] articles,
           java.lang.String action,
           java.lang.String layoutId,
           java.lang.String editionId,
           com.woodwing.enterprise.interfaces.services.wfl.PreviewType previewType,
           java.lang.String[] requestInfo) {
           this.ticket = ticket;
           this.workspaceId = workspaceId;
           this.articles = articles;
           this.action = action;
           this.layoutId = layoutId;
           this.editionId = editionId;
           this.previewType = previewType;
           this.requestInfo = requestInfo;
    }


    /**
     * Gets the ticket value for this PreviewArticlesAtWorkspace.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this PreviewArticlesAtWorkspace.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the workspaceId value for this PreviewArticlesAtWorkspace.
     * 
     * @return workspaceId
     */
    public java.lang.String getWorkspaceId() {
        return workspaceId;
    }


    /**
     * Sets the workspaceId value for this PreviewArticlesAtWorkspace.
     * 
     * @param workspaceId
     */
    public void setWorkspaceId(java.lang.String workspaceId) {
        this.workspaceId = workspaceId;
    }


    /**
     * Gets the articles value for this PreviewArticlesAtWorkspace.
     * 
     * @return articles
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ArticleAtWorkspace[] getArticles() {
        return articles;
    }


    /**
     * Sets the articles value for this PreviewArticlesAtWorkspace.
     * 
     * @param articles
     */
    public void setArticles(com.woodwing.enterprise.interfaces.services.wfl.ArticleAtWorkspace[] articles) {
        this.articles = articles;
    }


    /**
     * Gets the action value for this PreviewArticlesAtWorkspace.
     * 
     * @return action
     */
    public java.lang.String getAction() {
        return action;
    }


    /**
     * Sets the action value for this PreviewArticlesAtWorkspace.
     * 
     * @param action
     */
    public void setAction(java.lang.String action) {
        this.action = action;
    }


    /**
     * Gets the layoutId value for this PreviewArticlesAtWorkspace.
     * 
     * @return layoutId
     */
    public java.lang.String getLayoutId() {
        return layoutId;
    }


    /**
     * Sets the layoutId value for this PreviewArticlesAtWorkspace.
     * 
     * @param layoutId
     */
    public void setLayoutId(java.lang.String layoutId) {
        this.layoutId = layoutId;
    }


    /**
     * Gets the editionId value for this PreviewArticlesAtWorkspace.
     * 
     * @return editionId
     */
    public java.lang.String getEditionId() {
        return editionId;
    }


    /**
     * Sets the editionId value for this PreviewArticlesAtWorkspace.
     * 
     * @param editionId
     */
    public void setEditionId(java.lang.String editionId) {
        this.editionId = editionId;
    }


    /**
     * Gets the previewType value for this PreviewArticlesAtWorkspace.
     * 
     * @return previewType
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PreviewType getPreviewType() {
        return previewType;
    }


    /**
     * Sets the previewType value for this PreviewArticlesAtWorkspace.
     * 
     * @param previewType
     */
    public void setPreviewType(com.woodwing.enterprise.interfaces.services.wfl.PreviewType previewType) {
        this.previewType = previewType;
    }


    /**
     * Gets the requestInfo value for this PreviewArticlesAtWorkspace.
     * 
     * @return requestInfo
     */
    public java.lang.String[] getRequestInfo() {
        return requestInfo;
    }


    /**
     * Sets the requestInfo value for this PreviewArticlesAtWorkspace.
     * 
     * @param requestInfo
     */
    public void setRequestInfo(java.lang.String[] requestInfo) {
        this.requestInfo = requestInfo;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PreviewArticlesAtWorkspace)) return false;
        PreviewArticlesAtWorkspace other = (PreviewArticlesAtWorkspace) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.workspaceId==null && other.getWorkspaceId()==null) || 
             (this.workspaceId!=null &&
              this.workspaceId.equals(other.getWorkspaceId()))) &&
            ((this.articles==null && other.getArticles()==null) || 
             (this.articles!=null &&
              java.util.Arrays.equals(this.articles, other.getArticles()))) &&
            ((this.action==null && other.getAction()==null) || 
             (this.action!=null &&
              this.action.equals(other.getAction()))) &&
            ((this.layoutId==null && other.getLayoutId()==null) || 
             (this.layoutId!=null &&
              this.layoutId.equals(other.getLayoutId()))) &&
            ((this.editionId==null && other.getEditionId()==null) || 
             (this.editionId!=null &&
              this.editionId.equals(other.getEditionId()))) &&
            ((this.previewType==null && other.getPreviewType()==null) || 
             (this.previewType!=null &&
              this.previewType.equals(other.getPreviewType()))) &&
            ((this.requestInfo==null && other.getRequestInfo()==null) || 
             (this.requestInfo!=null &&
              java.util.Arrays.equals(this.requestInfo, other.getRequestInfo())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getWorkspaceId() != null) {
            _hashCode += getWorkspaceId().hashCode();
        }
        if (getArticles() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getArticles());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getArticles(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getAction() != null) {
            _hashCode += getAction().hashCode();
        }
        if (getLayoutId() != null) {
            _hashCode += getLayoutId().hashCode();
        }
        if (getEditionId() != null) {
            _hashCode += getEditionId().hashCode();
        }
        if (getPreviewType() != null) {
            _hashCode += getPreviewType().hashCode();
        }
        if (getRequestInfo() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRequestInfo());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRequestInfo(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PreviewArticlesAtWorkspace.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticlesAtWorkspace"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("workspaceId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "WorkspaceId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("articles");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Articles"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ArticleAtWorkspace"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ArticleAtWorkspace"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("action");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Action"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("layoutId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LayoutId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editionId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EditionId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("previewType");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PreviewType"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PreviewType"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
