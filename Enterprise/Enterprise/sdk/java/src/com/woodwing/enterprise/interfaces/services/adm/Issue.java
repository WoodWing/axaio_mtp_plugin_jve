/**
 * Issue.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class Issue  implements java.io.Serializable {
    private java.math.BigInteger id;

    private java.lang.String name;

    private java.lang.String description;

    private org.apache.axis.types.UnsignedInt sortOrder;

    private java.lang.Boolean emailNotify;

    private java.lang.Boolean reversedRead;

    private java.lang.Boolean overrulePublication;

    private com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty deadline;

    private org.apache.axis.types.UnsignedInt expectedPages;

    private java.lang.String subject;

    private java.lang.Boolean activated;

    private com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty publicationDate;

    private com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] extraMetaData;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] editions;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] sections;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] statuses;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] userGroups;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] workflows;

    private com.woodwing.enterprise.interfaces.services.adm.IdName[] routings;

    private java.lang.Boolean calculateDeadlines;

    public Issue() {
    }

    public Issue(
           java.math.BigInteger id,
           java.lang.String name,
           java.lang.String description,
           org.apache.axis.types.UnsignedInt sortOrder,
           java.lang.Boolean emailNotify,
           java.lang.Boolean reversedRead,
           java.lang.Boolean overrulePublication,
           com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty deadline,
           org.apache.axis.types.UnsignedInt expectedPages,
           java.lang.String subject,
           java.lang.Boolean activated,
           com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty publicationDate,
           com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] extraMetaData,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] editions,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] sections,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] statuses,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] userGroups,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] workflows,
           com.woodwing.enterprise.interfaces.services.adm.IdName[] routings,
           java.lang.Boolean calculateDeadlines) {
           this.id = id;
           this.name = name;
           this.description = description;
           this.sortOrder = sortOrder;
           this.emailNotify = emailNotify;
           this.reversedRead = reversedRead;
           this.overrulePublication = overrulePublication;
           this.deadline = deadline;
           this.expectedPages = expectedPages;
           this.subject = subject;
           this.activated = activated;
           this.publicationDate = publicationDate;
           this.extraMetaData = extraMetaData;
           this.editions = editions;
           this.sections = sections;
           this.statuses = statuses;
           this.userGroups = userGroups;
           this.workflows = workflows;
           this.routings = routings;
           this.calculateDeadlines = calculateDeadlines;
    }


    /**
     * Gets the id value for this Issue.
     * 
     * @return id
     */
    public java.math.BigInteger getId() {
        return id;
    }


    /**
     * Sets the id value for this Issue.
     * 
     * @param id
     */
    public void setId(java.math.BigInteger id) {
        this.id = id;
    }


    /**
     * Gets the name value for this Issue.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this Issue.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the description value for this Issue.
     * 
     * @return description
     */
    public java.lang.String getDescription() {
        return description;
    }


    /**
     * Sets the description value for this Issue.
     * 
     * @param description
     */
    public void setDescription(java.lang.String description) {
        this.description = description;
    }


    /**
     * Gets the sortOrder value for this Issue.
     * 
     * @return sortOrder
     */
    public org.apache.axis.types.UnsignedInt getSortOrder() {
        return sortOrder;
    }


    /**
     * Sets the sortOrder value for this Issue.
     * 
     * @param sortOrder
     */
    public void setSortOrder(org.apache.axis.types.UnsignedInt sortOrder) {
        this.sortOrder = sortOrder;
    }


    /**
     * Gets the emailNotify value for this Issue.
     * 
     * @return emailNotify
     */
    public java.lang.Boolean getEmailNotify() {
        return emailNotify;
    }


    /**
     * Sets the emailNotify value for this Issue.
     * 
     * @param emailNotify
     */
    public void setEmailNotify(java.lang.Boolean emailNotify) {
        this.emailNotify = emailNotify;
    }


    /**
     * Gets the reversedRead value for this Issue.
     * 
     * @return reversedRead
     */
    public java.lang.Boolean getReversedRead() {
        return reversedRead;
    }


    /**
     * Sets the reversedRead value for this Issue.
     * 
     * @param reversedRead
     */
    public void setReversedRead(java.lang.Boolean reversedRead) {
        this.reversedRead = reversedRead;
    }


    /**
     * Gets the overrulePublication value for this Issue.
     * 
     * @return overrulePublication
     */
    public java.lang.Boolean getOverrulePublication() {
        return overrulePublication;
    }


    /**
     * Sets the overrulePublication value for this Issue.
     * 
     * @param overrulePublication
     */
    public void setOverrulePublication(java.lang.Boolean overrulePublication) {
        this.overrulePublication = overrulePublication;
    }


    /**
     * Gets the deadline value for this Issue.
     * 
     * @return deadline
     */
    public com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty getDeadline() {
        return deadline;
    }


    /**
     * Sets the deadline value for this Issue.
     * 
     * @param deadline
     */
    public void setDeadline(com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty deadline) {
        this.deadline = deadline;
    }


    /**
     * Gets the expectedPages value for this Issue.
     * 
     * @return expectedPages
     */
    public org.apache.axis.types.UnsignedInt getExpectedPages() {
        return expectedPages;
    }


    /**
     * Sets the expectedPages value for this Issue.
     * 
     * @param expectedPages
     */
    public void setExpectedPages(org.apache.axis.types.UnsignedInt expectedPages) {
        this.expectedPages = expectedPages;
    }


    /**
     * Gets the subject value for this Issue.
     * 
     * @return subject
     */
    public java.lang.String getSubject() {
        return subject;
    }


    /**
     * Sets the subject value for this Issue.
     * 
     * @param subject
     */
    public void setSubject(java.lang.String subject) {
        this.subject = subject;
    }


    /**
     * Gets the activated value for this Issue.
     * 
     * @return activated
     */
    public java.lang.Boolean getActivated() {
        return activated;
    }


    /**
     * Sets the activated value for this Issue.
     * 
     * @param activated
     */
    public void setActivated(java.lang.Boolean activated) {
        this.activated = activated;
    }


    /**
     * Gets the publicationDate value for this Issue.
     * 
     * @return publicationDate
     */
    public com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty getPublicationDate() {
        return publicationDate;
    }


    /**
     * Sets the publicationDate value for this Issue.
     * 
     * @param publicationDate
     */
    public void setPublicationDate(com.woodwing.enterprise.interfaces.services.adm.DateTimeOrEmpty publicationDate) {
        this.publicationDate = publicationDate;
    }


    /**
     * Gets the extraMetaData value for this Issue.
     * 
     * @return extraMetaData
     */
    public com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] getExtraMetaData() {
        return extraMetaData;
    }


    /**
     * Sets the extraMetaData value for this Issue.
     * 
     * @param extraMetaData
     */
    public void setExtraMetaData(com.woodwing.enterprise.interfaces.services.adm.ExtraMetaData[] extraMetaData) {
        this.extraMetaData = extraMetaData;
    }


    /**
     * Gets the editions value for this Issue.
     * 
     * @return editions
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getEditions() {
        return editions;
    }


    /**
     * Sets the editions value for this Issue.
     * 
     * @param editions
     */
    public void setEditions(com.woodwing.enterprise.interfaces.services.adm.IdName[] editions) {
        this.editions = editions;
    }


    /**
     * Gets the sections value for this Issue.
     * 
     * @return sections
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getSections() {
        return sections;
    }


    /**
     * Sets the sections value for this Issue.
     * 
     * @param sections
     */
    public void setSections(com.woodwing.enterprise.interfaces.services.adm.IdName[] sections) {
        this.sections = sections;
    }


    /**
     * Gets the statuses value for this Issue.
     * 
     * @return statuses
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getStatuses() {
        return statuses;
    }


    /**
     * Sets the statuses value for this Issue.
     * 
     * @param statuses
     */
    public void setStatuses(com.woodwing.enterprise.interfaces.services.adm.IdName[] statuses) {
        this.statuses = statuses;
    }


    /**
     * Gets the userGroups value for this Issue.
     * 
     * @return userGroups
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getUserGroups() {
        return userGroups;
    }


    /**
     * Sets the userGroups value for this Issue.
     * 
     * @param userGroups
     */
    public void setUserGroups(com.woodwing.enterprise.interfaces.services.adm.IdName[] userGroups) {
        this.userGroups = userGroups;
    }


    /**
     * Gets the workflows value for this Issue.
     * 
     * @return workflows
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getWorkflows() {
        return workflows;
    }


    /**
     * Sets the workflows value for this Issue.
     * 
     * @param workflows
     */
    public void setWorkflows(com.woodwing.enterprise.interfaces.services.adm.IdName[] workflows) {
        this.workflows = workflows;
    }


    /**
     * Gets the routings value for this Issue.
     * 
     * @return routings
     */
    public com.woodwing.enterprise.interfaces.services.adm.IdName[] getRoutings() {
        return routings;
    }


    /**
     * Sets the routings value for this Issue.
     * 
     * @param routings
     */
    public void setRoutings(com.woodwing.enterprise.interfaces.services.adm.IdName[] routings) {
        this.routings = routings;
    }


    /**
     * Gets the calculateDeadlines value for this Issue.
     * 
     * @return calculateDeadlines
     */
    public java.lang.Boolean getCalculateDeadlines() {
        return calculateDeadlines;
    }


    /**
     * Sets the calculateDeadlines value for this Issue.
     * 
     * @param calculateDeadlines
     */
    public void setCalculateDeadlines(java.lang.Boolean calculateDeadlines) {
        this.calculateDeadlines = calculateDeadlines;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Issue)) return false;
        Issue other = (Issue) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.id==null && other.getId()==null) || 
             (this.id!=null &&
              this.id.equals(other.getId()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.description==null && other.getDescription()==null) || 
             (this.description!=null &&
              this.description.equals(other.getDescription()))) &&
            ((this.sortOrder==null && other.getSortOrder()==null) || 
             (this.sortOrder!=null &&
              this.sortOrder.equals(other.getSortOrder()))) &&
            ((this.emailNotify==null && other.getEmailNotify()==null) || 
             (this.emailNotify!=null &&
              this.emailNotify.equals(other.getEmailNotify()))) &&
            ((this.reversedRead==null && other.getReversedRead()==null) || 
             (this.reversedRead!=null &&
              this.reversedRead.equals(other.getReversedRead()))) &&
            ((this.overrulePublication==null && other.getOverrulePublication()==null) || 
             (this.overrulePublication!=null &&
              this.overrulePublication.equals(other.getOverrulePublication()))) &&
            ((this.deadline==null && other.getDeadline()==null) || 
             (this.deadline!=null &&
              this.deadline.equals(other.getDeadline()))) &&
            ((this.expectedPages==null && other.getExpectedPages()==null) || 
             (this.expectedPages!=null &&
              this.expectedPages.equals(other.getExpectedPages()))) &&
            ((this.subject==null && other.getSubject()==null) || 
             (this.subject!=null &&
              this.subject.equals(other.getSubject()))) &&
            ((this.activated==null && other.getActivated()==null) || 
             (this.activated!=null &&
              this.activated.equals(other.getActivated()))) &&
            ((this.publicationDate==null && other.getPublicationDate()==null) || 
             (this.publicationDate!=null &&
              this.publicationDate.equals(other.getPublicationDate()))) &&
            ((this.extraMetaData==null && other.getExtraMetaData()==null) || 
             (this.extraMetaData!=null &&
              java.util.Arrays.equals(this.extraMetaData, other.getExtraMetaData()))) &&
            ((this.editions==null && other.getEditions()==null) || 
             (this.editions!=null &&
              java.util.Arrays.equals(this.editions, other.getEditions()))) &&
            ((this.sections==null && other.getSections()==null) || 
             (this.sections!=null &&
              java.util.Arrays.equals(this.sections, other.getSections()))) &&
            ((this.statuses==null && other.getStatuses()==null) || 
             (this.statuses!=null &&
              java.util.Arrays.equals(this.statuses, other.getStatuses()))) &&
            ((this.userGroups==null && other.getUserGroups()==null) || 
             (this.userGroups!=null &&
              java.util.Arrays.equals(this.userGroups, other.getUserGroups()))) &&
            ((this.workflows==null && other.getWorkflows()==null) || 
             (this.workflows!=null &&
              java.util.Arrays.equals(this.workflows, other.getWorkflows()))) &&
            ((this.routings==null && other.getRoutings()==null) || 
             (this.routings!=null &&
              java.util.Arrays.equals(this.routings, other.getRoutings()))) &&
            ((this.calculateDeadlines==null && other.getCalculateDeadlines()==null) || 
             (this.calculateDeadlines!=null &&
              this.calculateDeadlines.equals(other.getCalculateDeadlines())));
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
        if (getId() != null) {
            _hashCode += getId().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getDescription() != null) {
            _hashCode += getDescription().hashCode();
        }
        if (getSortOrder() != null) {
            _hashCode += getSortOrder().hashCode();
        }
        if (getEmailNotify() != null) {
            _hashCode += getEmailNotify().hashCode();
        }
        if (getReversedRead() != null) {
            _hashCode += getReversedRead().hashCode();
        }
        if (getOverrulePublication() != null) {
            _hashCode += getOverrulePublication().hashCode();
        }
        if (getDeadline() != null) {
            _hashCode += getDeadline().hashCode();
        }
        if (getExpectedPages() != null) {
            _hashCode += getExpectedPages().hashCode();
        }
        if (getSubject() != null) {
            _hashCode += getSubject().hashCode();
        }
        if (getActivated() != null) {
            _hashCode += getActivated().hashCode();
        }
        if (getPublicationDate() != null) {
            _hashCode += getPublicationDate().hashCode();
        }
        if (getExtraMetaData() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getExtraMetaData());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getExtraMetaData(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getEditions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getEditions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getEditions(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getSections() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSections());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSections(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getStatuses() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getStatuses());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getStatuses(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getUserGroups() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getUserGroups());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getUserGroups(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getWorkflows() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getWorkflows());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getWorkflows(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRoutings() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRoutings());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRoutings(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getCalculateDeadlines() != null) {
            _hashCode += getCalculateDeadlines().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Issue.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Issue"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "integer"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("description");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Description"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sortOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SortOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("emailNotify");
        elemField.setXmlName(new javax.xml.namespace.QName("", "EmailNotify"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("reversedRead");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReversedRead"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("overrulePublication");
        elemField.setXmlName(new javax.xml.namespace.QName("", "OverrulePublication"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("deadline");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Deadline"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "dateTimeOrEmpty"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("expectedPages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ExpectedPages"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("subject");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Subject"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("activated");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Activated"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publicationDate");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublicationDate"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "dateTimeOrEmpty"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("extraMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ExtraMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "ExtraMetaData"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ExtraMetaData"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Editions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sections");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Sections"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("statuses");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Statuses"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("userGroups");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UserGroups"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("workflows");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Workflows"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("routings");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Routings"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "IdName"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "IdName"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("calculateDeadlines");
        elemField.setXmlName(new javax.xml.namespace.QName("", "CalculateDeadlines"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
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
