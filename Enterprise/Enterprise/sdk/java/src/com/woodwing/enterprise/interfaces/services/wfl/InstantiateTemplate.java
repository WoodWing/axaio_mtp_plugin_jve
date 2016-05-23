/**
 * InstantiateTemplate.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class InstantiateTemplate  implements java.io.Serializable {
    private java.lang.String ticket;

    private boolean lock;

    private com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition;

    private java.lang.String[] requestInfo;

    private java.lang.String templateId;

    private com.woodwing.enterprise.interfaces.services.wfl.Object[] objects;

    public InstantiateTemplate() {
    }

    public InstantiateTemplate(
           java.lang.String ticket,
           boolean lock,
           com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition,
           java.lang.String[] requestInfo,
           java.lang.String templateId,
           com.woodwing.enterprise.interfaces.services.wfl.Object[] objects) {
           this.ticket = ticket;
           this.lock = lock;
           this.rendition = rendition;
           this.requestInfo = requestInfo;
           this.templateId = templateId;
           this.objects = objects;
    }


    /**
     * Gets the ticket value for this InstantiateTemplate.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this InstantiateTemplate.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the lock value for this InstantiateTemplate.
     * 
     * @return lock
     */
    public boolean isLock() {
        return lock;
    }


    /**
     * Sets the lock value for this InstantiateTemplate.
     * 
     * @param lock
     */
    public void setLock(boolean lock) {
        this.lock = lock;
    }


    /**
     * Gets the rendition value for this InstantiateTemplate.
     * 
     * @return rendition
     */
    public com.woodwing.enterprise.interfaces.services.wfl.RenditionType getRendition() {
        return rendition;
    }


    /**
     * Sets the rendition value for this InstantiateTemplate.
     * 
     * @param rendition
     */
    public void setRendition(com.woodwing.enterprise.interfaces.services.wfl.RenditionType rendition) {
        this.rendition = rendition;
    }


    /**
     * Gets the requestInfo value for this InstantiateTemplate.
     * 
     * @return requestInfo
     */
    public java.lang.String[] getRequestInfo() {
        return requestInfo;
    }


    /**
     * Sets the requestInfo value for this InstantiateTemplate.
     * 
     * @param requestInfo
     */
    public void setRequestInfo(java.lang.String[] requestInfo) {
        this.requestInfo = requestInfo;
    }


    /**
     * Gets the templateId value for this InstantiateTemplate.
     * 
     * @return templateId
     */
    public java.lang.String getTemplateId() {
        return templateId;
    }


    /**
     * Sets the templateId value for this InstantiateTemplate.
     * 
     * @param templateId
     */
    public void setTemplateId(java.lang.String templateId) {
        this.templateId = templateId;
    }


    /**
     * Gets the objects value for this InstantiateTemplate.
     * 
     * @return objects
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Object[] getObjects() {
        return objects;
    }


    /**
     * Sets the objects value for this InstantiateTemplate.
     * 
     * @param objects
     */
    public void setObjects(com.woodwing.enterprise.interfaces.services.wfl.Object[] objects) {
        this.objects = objects;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof InstantiateTemplate)) return false;
        InstantiateTemplate other = (InstantiateTemplate) obj;
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
            this.lock == other.isLock() &&
            ((this.rendition==null && other.getRendition()==null) || 
             (this.rendition!=null &&
              this.rendition.equals(other.getRendition()))) &&
            ((this.requestInfo==null && other.getRequestInfo()==null) || 
             (this.requestInfo!=null &&
              java.util.Arrays.equals(this.requestInfo, other.getRequestInfo()))) &&
            ((this.templateId==null && other.getTemplateId()==null) || 
             (this.templateId!=null &&
              this.templateId.equals(other.getTemplateId()))) &&
            ((this.objects==null && other.getObjects()==null) || 
             (this.objects!=null &&
              java.util.Arrays.equals(this.objects, other.getObjects())));
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
        _hashCode += (isLock() ? Boolean.TRUE : Boolean.FALSE).hashCode();
        if (getRendition() != null) {
            _hashCode += getRendition().hashCode();
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
        if (getTemplateId() != null) {
            _hashCode += getTemplateId().hashCode();
        }
        if (getObjects() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getObjects());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getObjects(), i);
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
        new org.apache.axis.description.TypeDesc(InstantiateTemplate.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">InstantiateTemplate"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("lock");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Lock"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rendition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Rendition"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RenditionType"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("requestInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RequestInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("templateId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TemplateId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objects");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Objects"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Object"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Object"));
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
