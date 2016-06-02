/**
 * PreviewArticleAtWorkspace.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class PreviewArticleAtWorkspace  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String workspaceId;

    private java.lang.String ID;

    private java.lang.String format;

    private java.lang.String content;

    private com.woodwing.enterprise.interfaces.services.wfl.Element[] elements;

    private java.lang.String action;

    private java.lang.String layoutId;

    private java.lang.String editionId;

    private com.woodwing.enterprise.interfaces.services.wfl.PreviewType previewType;

    private java.lang.String[] requestInfo;

    public PreviewArticleAtWorkspace() {
    }

    public PreviewArticleAtWorkspace(
           java.lang.String ticket,
           java.lang.String workspaceId,
           java.lang.String ID,
           java.lang.String format,
           java.lang.String content,
           com.woodwing.enterprise.interfaces.services.wfl.Element[] elements,
           java.lang.String action,
           java.lang.String layoutId,
           java.lang.String editionId,
           com.woodwing.enterprise.interfaces.services.wfl.PreviewType previewType,
           java.lang.String[] requestInfo) {
           this.ticket = ticket;
           this.workspaceId = workspaceId;
           this.ID = ID;
           this.format = format;
           this.content = content;
           this.elements = elements;
           this.action = action;
           this.layoutId = layoutId;
           this.editionId = editionId;
           this.previewType = previewType;
           this.requestInfo = requestInfo;
    }


    /**
     * Gets the ticket value for this PreviewArticleAtWorkspace.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this PreviewArticleAtWorkspace.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the workspaceId value for this PreviewArticleAtWorkspace.
     * 
     * @return workspaceId
     */
    public java.lang.String getWorkspaceId() {
        return workspaceId;
    }


    /**
     * Sets the workspaceId value for this PreviewArticleAtWorkspace.
     * 
     * @param workspaceId
     */
    public void setWorkspaceId(java.lang.String workspaceId) {
        this.workspaceId = workspaceId;
    }


    /**
     * Gets the ID value for this PreviewArticleAtWorkspace.
     * 
     * @return ID
     */
    public java.lang.String getID() {
        return ID;
    }


    /**
     * Sets the ID value for this PreviewArticleAtWorkspace.
     * 
     * @param ID
     */
    public void setID(java.lang.String ID) {
        this.ID = ID;
    }


    /**
     * Gets the format value for this PreviewArticleAtWorkspace.
     * 
     * @return format
     */
    public java.lang.String getFormat() {
        return format;
    }


    /**
     * Sets the format value for this PreviewArticleAtWorkspace.
     * 
     * @param format
     */
    public void setFormat(java.lang.String format) {
        this.format = format;
    }


    /**
     * Gets the content value for this PreviewArticleAtWorkspace.
     * 
     * @return content
     */
    public java.lang.String getContent() {
        return content;
    }


    /**
     * Sets the content value for this PreviewArticleAtWorkspace.
     * 
     * @param content
     */
    public void setContent(java.lang.String content) {
        this.content = content;
    }


    /**
     * Gets the elements value for this PreviewArticleAtWorkspace.
     * 
     * @return elements
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Element[] getElements() {
        return elements;
    }


    /**
     * Sets the elements value for this PreviewArticleAtWorkspace.
     * 
     * @param elements
     */
    public void setElements(com.woodwing.enterprise.interfaces.services.wfl.Element[] elements) {
        this.elements = elements;
    }


    /**
     * Gets the action value for this PreviewArticleAtWorkspace.
     * 
     * @return action
     */
    public java.lang.String getAction() {
        return action;
    }


    /**
     * Sets the action value for this PreviewArticleAtWorkspace.
     * 
     * @param action
     */
    public void setAction(java.lang.String action) {
        this.action = action;
    }


    /**
     * Gets the layoutId value for this PreviewArticleAtWorkspace.
     * 
     * @return layoutId
     */
    public java.lang.String getLayoutId() {
        return layoutId;
    }


    /**
     * Sets the layoutId value for this PreviewArticleAtWorkspace.
     * 
     * @param layoutId
     */
    public void setLayoutId(java.lang.String layoutId) {
        this.layoutId = layoutId;
    }


    /**
     * Gets the editionId value for this PreviewArticleAtWorkspace.
     * 
     * @return editionId
     */
    public java.lang.String getEditionId() {
        return editionId;
    }


    /**
     * Sets the editionId value for this PreviewArticleAtWorkspace.
     * 
     * @param editionId
     */
    public void setEditionId(java.lang.String editionId) {
        this.editionId = editionId;
    }


    /**
     * Gets the previewType value for this PreviewArticleAtWorkspace.
     * 
     * @return previewType
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PreviewType getPreviewType() {
        return previewType;
    }


    /**
     * Sets the previewType value for this PreviewArticleAtWorkspace.
     * 
     * @param previewType
     */
    public void setPreviewType(com.woodwing.enterprise.interfaces.services.wfl.PreviewType previewType) {
        this.previewType = previewType;
    }


    /**
     * Gets the requestInfo value for this PreviewArticleAtWorkspace.
     * 
     * @return requestInfo
     */
    public java.lang.String[] getRequestInfo() {
        return requestInfo;
    }


    /**
     * Sets the requestInfo value for this PreviewArticleAtWorkspace.
     * 
     * @param requestInfo
     */
    public void setRequestInfo(java.lang.String[] requestInfo) {
        this.requestInfo = requestInfo;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PreviewArticleAtWorkspace)) return false;
        PreviewArticleAtWorkspace other = (PreviewArticleAtWorkspace) obj;
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
            ((this.ID==null && other.getID()==null) || 
             (this.ID!=null &&
              this.ID.equals(other.getID()))) &&
            ((this.format==null && other.getFormat()==null) || 
             (this.format!=null &&
              this.format.equals(other.getFormat()))) &&
            ((this.content==null && other.getContent()==null) || 
             (this.content!=null &&
              this.content.equals(other.getContent()))) &&
            ((this.elements==null && other.getElements()==null) || 
             (this.elements!=null &&
              java.util.Arrays.equals(this.elements, other.getElements()))) &&
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
        if (getID() != null) {
            _hashCode += getID().hashCode();
        }
        if (getFormat() != null) {
            _hashCode += getFormat().hashCode();
        }
        if (getContent() != null) {
            _hashCode += getContent().hashCode();
        }
        if (getElements() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getElements());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getElements(), i);
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
        new org.apache.axis.description.TypeDesc(PreviewArticleAtWorkspace.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticleAtWorkspace"));
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
        elemField.setFieldName("ID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("format");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Format"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("content");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Content"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("elements");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Elements"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Element"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Element"));
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
